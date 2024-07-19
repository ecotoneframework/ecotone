<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * licence Apache-2.0
 */
class ConsoleCommandConfiguration implements DefinedObject
{
    public const HEADER_PARAMETER_NAME = 'header';
    public const HEADER_NAME = 'console_headers';

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
        $parameterNames[] = ConsoleCommandParameter::createWithDefaultValue(
            self::HEADER_PARAMETER_NAME,
            '',
            true,
            true,
            [],
        );

        $this->parameterNames = $parameterNames;
    }

    public function getHeaderNameForParameterName(string $parameterName): string
    {
        foreach ($this->parameterNames as $consoleCommandParameter) {
            if ($consoleCommandParameter->getName() == $parameterName) {
                return $consoleCommandParameter->getMessageHeaderName();
            }
        }

        throw InvalidArgumentException::create("Can't find console parameter with name {$parameterName}");
    }

    /**
     * @var ConsoleCommandParameter[] $parameterNames
     */
    public static function create(string $channelName, string $name, array $parameterNames): self
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

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->channelName,
            $this->name,
            $this->parameterNames,
        ], [self::class, 'create']);
    }
}
