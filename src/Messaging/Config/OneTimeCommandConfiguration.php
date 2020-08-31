<?php

namespace Ecotone\Messaging\Config;

class OneTimeCommandConfiguration
{
    private string $name;
    private string $channelName;
    /**
     * @var OneTimeCommandParameter[]
     */
    private array $parameterNames;

    /**
     * @var OneTimeCommandParameter[] $parameterNames
     */
    private function __construct(string $channelName, string $name, array $parameterNames)
    {
        $this->name               = $name;
        $this->channelName = $channelName;
        $this->parameterNames = $parameterNames;
    }

    /**
     * @var OneTimeCommandParameter[] $parameterNames
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
     * @return OneTimeCommandParameter[]
     */
    public function getParameters(): array
    {
        return $this->parameterNames;
    }
}