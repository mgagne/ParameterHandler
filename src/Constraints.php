<?php

namespace Inap\ParameterHandler;

class Constraints
{
    public $constraints = array();

    public function __construct(array $constraints)
    {
        $this->constraints = $constraints;
    }

    public function isValid($value)
    {
        foreach ($this->constraints as $constraint)
        {
            $constraint->isValid($value);
        }
    }

    public static function fromConstraints(array $constraints)
    {
        $constraintsObjects = array();

        foreach ($constraints as $constraint)
        {
            array_push($constraintsObjects, Constraints::fromConstraint($constraint));
        }

        return new Constraints($constraintsObjects);
    }

    private static function fromConstraint(array $constraint)
    {
        if (isset($constraint['length']))
        {
            $constraint = new Length(
                Constraints::getValue($constraint['length'], 'min'),
                Constraints::getValue($constraint['length'], 'max'));
        }
        elseif (isset($constraint['range']))
        {
            $constraint = new Range(
                Constraints::getValue($constraint['range'], 'min'),
                Constraints::getValue($constraint['range'], 'max'));
        }
        elseif (isset($constraint['allowed_values']))
        {
            $constraint = new AllowedValues($constraint['allowed_values']);
        }
        elseif (isset($constraint['allowed_pattern']))
        {
            $constraint = new AllowedPattern($constraint['allowed_pattern']);
        }
        else
        {
            throw new \InvalidArgumentException('Unknown constraint definition');
        }

        return $constraint;
    }

    private static function getValue(array $definition, string $name, $defaultValue = null)
    {
        return array_key_exists($name, $definition) ? $definition[$name] : $defaultValue;
    }
}


abstract class Constraint
{
    public function isValid($value)
    {
        return True;
    }
}


class Length extends Constraint
{
    public function __construct(int $min = null, int $max = null)
    {
        if (($min === $max) && ($max === null))
        {
            throw new \InvalidArgumentException('Constraint requires at least one of min or max');
        }

        $this->min = $min;
        $this->max = $max;
    }

    public function isValid($value)
    {
        $len = strlen($value);
        if (isset($this->min) && ($len < $this->min))
        {
            throw new \InvalidArgumentException(sprintf('Value is too short (min: %u)', $this->min));
        }

        if (isset($this->max) && ($len < $this->max))
        {
            throw new \InvalidArgumentException(sprintf('Value is too long (max: %u)', $this->max));
        }
    }
}


class Range extends Constraint
{
    public function __construct(int $min = null, int $max = null)
    {
        if (($min === $max) && ($max === null))
        {
            throw new \InvalidArgumentException('Constraint requires at least one of min or max');
        }

        $this->min = $min;
        $this->max = $max;
    }

    public function isValid($value)
    {
        if (isset($this->min) && ($value < $this->min))
        {
            throw new \InvalidArgumentException(sprintf('Value is too low (min: %u)', $this->min));
        }

        if (isset($this->max) && ($value < $this->max))
        {
            throw new \InvalidArgumentException(sprintf('Value is too high (max: %u)', $this->max));
        }
    }
}


class AllowedValues extends Constraint
{
    public function __construct(array $allowedValues)
    {
        $this->allowedValues = $allowedValues;
    }

    public function isValid($value)
    {
        if (!in_array($value, $this->allowedValues))
        {
            throw new \InvalidArgumentException(sprintf('Value needs to be one of: %s', implode(', ', $this->allowedValues)));
        }
    }
}


class AllowedPattern extends Constraint
{
    public function __construct(string $allowedPattern)
    {
        $this->allowedPattern = $allowedPattern;
    }

    public function isValid($value)
    {
        $allowedPattern = '/' . $this->allowedPattern . '/';
        if (!preg_match($allowedPattern, $value))
        {
            throw new \InvalidArgumentException(sprintf('Value needs to match this pattern: %s', $this->allowedPattern));
        }
    }
}
