<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodCall;
use Ecotone\Messaging\Message;

/**
 * Class NoReplyMessageProducer
 * @package Test\Ecotone\Messaging\Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class NoReplyMessageProducer implements MessageProcessor
{
    private $wasCalled = false;

    public static function create(): self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function executeEndpoint(Message $message)
    {
        $this->wasCalled = true;
    }

    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }

    public function getMethodCall(Message $message): MethodCall
    {
        return MethodCall::createWith([], false);
    }

    public function getAroundMethodInterceptors(): array
    {
        return [];
    }

    public function getObjectToInvokeOn(): string|object
    {
        return self::class;
    }

    public function getEndpointAnnotations(): array
    {
        return [];
    }

    public function __toString()
    {
        return self::class;
    }
}
