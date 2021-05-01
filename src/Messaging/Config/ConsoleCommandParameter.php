<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Support\Assert;

class ConsoleCommandParameter
{
    private function __construct(private string $name, private string $messageHeaderName, private bool $isOption, private mixed $defaultValue, private bool $hasDefaultValue) {}

    public static function create(string $name, string $messageHeaderName, bool $isOption) : self
    {
        Assert::isFalse($isOption, "Console parameter with name `{$name}` is option (boolean), so it should have default value.");

        return new self($name, $messageHeaderName, $isOption, null, false);
    }

    public static function createWithDefaultValue(string $name, string $messageHeaderName, bool $isOption, $defaultValue) : self
    {
        if ($isOption && !is_bool($defaultValue)) {
            throw ConfigurationException::create("Console command parameter `{$name}` is option however the default value is not boolean");
        }

        return new self($name, $messageHeaderName, $isOption, $defaultValue,true);
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
}