<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * licence Apache-2.0
 */
class ConsoleCommandParameter implements DefinedObject
{
    public function __construct(private string $name, private string $messageHeaderName, private bool $isOption, private bool $isArray, private mixed $defaultValue, private bool $hasDefaultValue)
    {
    }

    public static function create(string $name, string $messageHeaderName, bool $isOption, bool $isArray = false): self
    {
        return new self($name, $messageHeaderName, $isOption, $isArray, null, false);
    }

    public static function createWithDefaultValue(string $name, string $messageHeaderName, bool $isOption, bool $isArray, mixed $defaultValue): self
    {
        return new self($name, $messageHeaderName, $isOption, $isArray, $defaultValue, true);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isOption(): bool
    {
        return $this->isOption;
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

    public function isArray(): bool
    {
        return $this->isArray;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->name,
            $this->messageHeaderName,
            $this->isOption,
            $this->isArray,
            $this->defaultValue,
            $this->hasDefaultValue,
        ]);
    }
}
