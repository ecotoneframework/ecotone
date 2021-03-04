<?php

namespace Ecotone\Messaging\Config;

class ConsoleCommandParameter
{
    private string $name;
    private mixed $defaultValue;
    private bool $hasDefaultValue;
    private string $messageHeaderName;

    private function __construct(string $name, string $messageHeaderName, mixed $defaultValue, bool $hasDefaultValue)
    {
        $this->name            = $name;
        $this->messageHeaderName = $messageHeaderName;
        $this->defaultValue    = $defaultValue;
        $this->hasDefaultValue = $hasDefaultValue;
    }

    public static function create(string $name, string $messageHeaderName) : self
    {
        return new self($name, $messageHeaderName, null, false);
    }

    public static function createWithDefaultValue(string $name, string $messageHeaderName, $defaultValue) : self
    {
        return new self($name, $messageHeaderName, $defaultValue, true);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMessageHeaderName(): string
    {
        return $this->messageHeaderName;
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