<?php

namespace Ecotone\Messaging\Config;

class OneTimeCommandParameter
{
    private string $name;
    private $defaultValue;
    private bool $hasDefaultValue;

    private function __construct(string $name, $defaultValue, bool $hasDefaultValue)
    {
        $this->name            = $name;
        $this->defaultValue    = $defaultValue;
        $this->hasDefaultValue = $hasDefaultValue;
    }

    public static function create(string $name) : self
    {
        return new self($name, null, false);
    }

    public static function createWithDefaultValue(string $name, $defaultValue) : self
    {
        return new self($name, $defaultValue, true);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function hasDefaultValue(): bool
    {
        return $this->hasDefaultValue;
    }
}