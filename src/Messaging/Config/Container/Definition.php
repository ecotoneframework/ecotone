<?php

namespace Ecotone\Messaging\Config\Container;

/**
 * licence Apache-2.0
 */
class Definition implements CompilableBuilder
{
    /**
     * @var MethodCall[]
     */
    private array $methodCalls = [];

    /**
     * @param array<string|int, mixed> $arguments
     */
    public function __construct(protected string $className, protected array $arguments = [], protected string|array $factory = '')
    {
    }

    /**
     * @param array<string|int, mixed> $arguments
     */
    public static function createFor(string $className, array $arguments): self
    {
        return new self($className, $arguments);
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getFactory(): array
    {
        if (is_string($this->factory)) {
            return [$this->className, $this->factory];
        }
        return $this->factory;
    }

    public function hasFactory(): bool
    {
        return ! empty($this->factory);
    }

    public function addMethodCall(string $string, array $array): self
    {
        $this->methodCalls[] = new MethodCall($string, $array);

        return $this;
    }

    /**
     * @return MethodCall[]
     */
    public function getMethodCalls(): array
    {
        return $this->methodCalls;
    }

    public function compile(MessagingContainerBuilder $builder): self
    {
        return $this;
    }
}
