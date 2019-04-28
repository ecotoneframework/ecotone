<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler;

use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class DumbMessageHandler
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler
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