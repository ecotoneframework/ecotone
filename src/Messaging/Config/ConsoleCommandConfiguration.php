<?php

namespace Ecotone\Messaging\Config;

class ConsoleCommandConfiguration
{
    private string $name;
    private string $channelName;
    /**
     * @var ConsoleCommandParameter[]
     */
    private array $parameterNames;

    /**
     * @var ConsoleCommandParameter[] $parameterNames
     */
    private function __construct(string $channelName, string $name, array $parameterNames)
    {
        $this->name               = $name;
        $this->channelName = $channelName;
        $this->parameterNames = $parameterNames;
    }

    /**
     * @var ConsoleCommandParameter[] $parameterNames
     */
    public static function create(string $channelName, string $name, array $parameterNames) : self
    {
        return new self($channelName, $name, $parameterNames);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getChannelName(): string
    {
        return $this->channelName;
    }

    /**
     * @return ConsoleCommandParameter[]
     */
    public function getParameters(): array
    {
        return $this->parameterNames;
    }
}