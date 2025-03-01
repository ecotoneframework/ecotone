<?php

namespace Ecotone\Messaging\Config\Container;

use Ecotone\Messaging\Config\Container\Compiler\ContainerImplementation;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Apache-2.0
 */
class Reference implements CompilableBuilder
{
    public function __construct(protected string $id, protected int $invalidBehavior = ContainerImplementation::EXCEPTION_ON_INVALID_REFERENCE)
    {
        Assert::notNullAndEmpty($id, "Id can't be empty");
    }

    public static function to(string $id): self
    {
        return new self($id);
    }

    public static function toChannel(string $id): ChannelReference
    {
        return new ChannelReference($id);
    }

    public static function toInterface(string $className, string $methodName): InterfaceToCallReference
    {
        return new InterfaceToCallReference($className, $methodName);
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns the behavior to be used when the service does not exist.
     */
    public function getInvalidBehavior(): int
    {
        return $this->invalidBehavior;
    }

    public function compile(MessagingContainerBuilder $builder): self
    {
        return $this;
    }

    public function __toString(): string
    {
        return $this->id;
    }

}
