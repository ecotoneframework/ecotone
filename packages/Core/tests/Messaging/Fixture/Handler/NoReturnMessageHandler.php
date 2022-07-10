<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;

/**
 * Class DumbMessageHandler
 * @package Test\Ecotone\Messaging\Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class NoReturnMessageHandler implements MessageHandler
{
    /** @var  bool */
    private $wasCalled = false;

    private $callCount = 0;

    public static function create() : self
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

    public function handleWithMethodInvocation(MethodInvocation $methodInvocation, Message $message) : void
    {
        $this->wasCalled = true;
        $this->callCount++;
    }

    public function wasCalled() : bool
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
}