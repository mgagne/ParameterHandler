<?php

namespace Inap\ParameterHandler;

use Composer\IO\IOInterface;
use Symfony\Component\Yaml\Inline as YamlInline;
use Symfony\Component\Yaml\Yaml;

class Processor
{
    private $io;

    public function __construct(IOInterface $io, array $config)
    {
        $this->io = $io;
        $this->config = $config;
    }

    public function process()
    {
        $config = $this->processConfig($this->config);
        $parametersFile = $config['parameters-file'];
        $parametersDistFile = $config['parameters-dist-file'];
        $parameterKey = $config['parameter-key'];
        $parametersDefinitions = $config['parameters'];

        $exists = is_file($parametersFile);
        $action = $exists ? 'Updating' : 'Creating';
        $this->io->write(sprintf('<info>%s the "%s" file</info>', $action, $parametersFile));

        if (!is_file($parametersDistFile))
        {
            throw new \InvalidArgumentException(sprintf('The parameters dist file "%s" does not exist.', $parametersDistFile));
        }

        $expectedValues = Yaml::parseFile($parametersDistFile);
        if (!isset($expectedValues[$parameterKey]))
        {
            throw new \InvalidArgumentException(sprintf('The top-level key %s is missing.', $parameterKey));
        }
        $expectedParams = (array) $expectedValues[$parameterKey];

        $actualValues = array_merge(
            $expectedValues,
            array($parameterKey => array())
        );

        if ($exists)
        {
            $existingValues = Yaml::parseFile($parametersFile);

            if ($existingValues === null)
            {
                $existingValues = array();
            }

            if (!is_array($existingValues))
            {
                throw new \InvalidArgumentException(sprintf('The existing "%s" file does not contain an array', $parametersFile));
            }

            $actualValues = array_merge($actualValues, $existingValues);
        }

        $actualValues[$parameterKey] = $this->processParams(
            $config, $expectedParams, (array) $actualValues[$parameterKey]
        );

        $this->writeParametersFile($parametersFile, $actualValues);
    }

    private function processConfig(array $config)
    {
        if (!is_file($config['definitions-file']))
        {
            throw new \InvalidArgumentException(sprintf('The definitions file "%s" does not exist. Check your definitions-file config or create it.', $config['definitions-file']));
        }

        $config = Yaml::parseFile($config['definitions-file']);

        if (!isset($config['ignore-unknown-parameters']))
        {
            $config['ignore-unknown-parameters'] = True;
        }

        if (empty($config['parameter-key']))
        {
            $config['parameter-key'] = 'parameters';
        }

        if (empty($config['parameters-file']))
        {
            throw new \InvalidArgumentException('The parameters-file config is required.');
        }

        if (empty($config['parameters-dist-file']))
        {
            $config['parameters-dist-file'] = $config['parameters-file'].'.dist';
        }

        if (empty($config['parameters']))
        {
            $config['parameters'] = array();
        }

        foreach ($config['parameters'] as $name => $definition)
        {
            $config['parameters'][$name] = Parameter::fromDefinition($name, $definition);
        }

        return $config;
    }

    private function processParams(array $config, array $expectedParams, array $actualValues)
    {
        $actualValues = array_intersect_key($actualValues, $expectedParams);

        foreach ($expectedParams as $expectedParamName => $defaultValue)
        {
            if (empty($config['parameters'][$expectedParamName]))
            {
                if (!$config['ignore-unknown-parameters'])
                {
                    throw new \InvalidArgumentException(sprintf('Missing required parameter definition for "%s"', $expectedParamName));
                }

                $config['parameters'][$expectedParamName] = Parameter::emptyDefinition($expectedParamName);
            }

            $actualValue = $actualValues[$expectedParamName] ?? null;
            $actualValues[$expectedParamName] = $this->processParam(
                $expectedParamName,
                $defaultValue,
                $actualValue,
                $config['parameters'][$expectedParamName]);
        }

        return $actualValues;
    }

    private function processParam(string $paramName, $defaultValue, $actualValue, Parameter $definition)
    {
        if ($definition->variable)
        {
            $value = getenv($definition->variable);
            if ($value)
            {
                $actualValue = YamlInline::parse($value);
            }
        }

        if (!$this->io->isInteractive())
        {
            $value = (isset($actualValue)) ? $actualValue : $defaultValue;
        }
        elseif (isset($actualValue))
        {
            $value = $actualValue;
        }
        else
        {
            $value = $this->ask($paramName, $defaultValue, $definition);
        }

        try
        {
            $definition->validateValue($value);
        }
        catch (\InvalidArgumentException $e)
        {
            throw new \InvalidArgumentException(sprintf('Parameter %s failed validation: %s', $paramName, $e->getMessage()));
        }

        return $value;
    }

    private function ask(string $paramName, $defaultValue, Parameter $definition)
    {
        static $first = True;

        if ($first)
        {
            $first = false;
            $this->io->write('<comment>Some parameters are missing. Please provide them.</comment>');
        }

        $defaultValue = YamlInline::dump($defaultValue);
        $value = $this->io->ask(sprintf('<question>%s</question> (<comment>%s</comment>): ', $paramName, $defaultValue), $defaultValue);

        return YamlInline::parse($value);
    }

    private function writeParametersFile(string $parametersFile, array $values)
    {
        if (!is_dir($dir = dirname($parametersFile)))
        {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $parametersFile,
            "# This file is auto-generated during the composer install\n".Yaml::dump($values, 99)
        );
    }
}
