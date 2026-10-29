<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;

/**
 * Class DumbMessageHandler
 * @package Test\Ecotone\Messaging\Fixture\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class NoReturnMessageHandler implements MessageHandler, DefinedObject
{
    /** @var  bool */
    private $wasCalled = false;

    private $callCount = 0;

    public static function create(): self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $this->wasCalled = true;
        $this->callCount++;
    }

    public function handleWithMethodInvocation(MethodInvocation $methodInvocation, Message $message): void
    {
        $this->wasCalled = true;
        $this->callCount++;
    }

    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }

    /**
     * @return int
     */
    public function getCallCount(): int
    {
        return $this->callCount;
    }


    public function __toString()
    {
        return self::class;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }
}
