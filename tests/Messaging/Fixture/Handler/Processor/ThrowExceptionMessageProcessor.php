<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodCall;
use Ecotone\Messaging\Message;
use Throwable;

/**
 * Class ThrowExceptionMessageProcessor
 * @package Test\Ecotone\Messaging\Fixture\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ThrowExceptionMessageProcessor implements MessageProcessor
{
    /**
     * @var Throwable
     */
    private $exception;

    private function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }

    public static function create(Throwable $exception): self
    {
        return new self($exception);
    }

    /**
     * @inheritDoc
     */
    public function executeEndpoint(Message $message)
    {
        throw $this->exception;
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

    /**
     * @return string
     */
    public function __toString(): string
    {
        return self::class;
    }
}
