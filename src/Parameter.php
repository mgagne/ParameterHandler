<?php

namespace Inap\ParameterHandler;

class Parameter
{
    public $name;
    public $type;
    public $required;
    public $constraints;
    public $variable;

    private static $validationMethods = array(
        'string'  => 'validateString',
        'boolean' => 'validateBoolean',
        'number'  => 'validateNumber',
        'list'    => 'validateList',
    );

    public function __construct(string $name, string $type, bool $required, object $constraints, string $variable = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->required = $required;
        $this->constraints = $constraints;
        $this->variable = $variable;
    }

    public static function fromDefinition(string $name, array $definition)
    {
        if (empty($definition['type']))
        {
            throw new \InvalidArgumentException(sprintf('Missing required parameter type for "%s"', $name));
        }

        if (!array_key_exists($definition['type'], self::$validationMethods))
        {
            throw new \InvalidArgumentException(sprintf('Invalid parameter type "%s" for "%s"', $definition['type'], $name));
        }

        if (!isset($definition['required']))
        {
            $definition['required'] = False;
        }

        if (!isset($definition['constraints']))
        {
            $definition['constraints'] = array();
        }

        $constraints = Constraints::fromConstraints($definition['constraints']);

        return new Parameter(
            $name,
            $definition['type'],
            $definition['required'],
            $constraints,
        );
    }

    public static function emptyDefinition(string $name)
    {
        return new Parameter($name, 'string', False, Constraints::fromConstraints(array()));
    }

    public function validateValue($value)
    {
        if ($this->required)
        {
            if (!isset($value))
            {
                throw new \InvalidArgumentException('Value is required');
            }
        }

        $validationMethod = self::$validationMethods[$this->type];
        $this->$validationMethod($value);
        $this->constraints->isValid($value);
    }

    private function validateString($value)
    {
        if (!is_string($value))
        {
            throw new \InvalidArgumentException('Value needs to be a string');
        }
    }

    private function validateBoolean($value)
    {
        if (!is_bool($value))
        {
            throw new \InvalidArgumentException('Value needs to be a boolean');
        }
    }

    private function validateNumber($value)
    {
        if (!is_numeric($value))
        {
            throw new \InvalidArgumentException('Value needs to be a number');
        }
    }

    private function validateList($value)
    {
        if (!is_iterable($value))
        {
            throw new \InvalidArgumentException('Value needs to be a list');
        }
    }
}
