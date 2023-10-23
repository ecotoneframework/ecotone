<?php

namespace Ecotone\Messaging\Config\Container;

/**
 * @internal
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
