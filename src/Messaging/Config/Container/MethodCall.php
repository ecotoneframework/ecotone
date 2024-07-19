<?php

namespace Ecotone\Messaging\Config\Container;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 */
class MethodCall
{
    public function __construct(private string $methodName, private array $arguments)
    {
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
