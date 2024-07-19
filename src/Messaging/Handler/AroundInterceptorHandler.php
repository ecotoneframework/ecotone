<?php

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundMethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundMethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
class AroundInterceptorHandler implements MessageHandler
{
    /**
     * @param  AroundMethodInterceptor[]
     */
    public function __construct(
        private array $aroundInterceptors,
        private MessageProcessor $messageProcessor
    ) {
    }

    public function handle(Message $message): void
    {
        $aroundMethodInvoker = new AroundMethodInvocation(
            $message,
            $this->aroundInterceptors,
            $this->messageProcessor,
        );

        /** Execute endpoint with around and sends reply. Important as endpoint reply channel is replaced in AroundMethodInvocation */
        $result = $aroundMethodInvoker->proceed();
        if ($message->getHeaders()->hasReplyChannel() && ! is_null($result)) {
            $result = $result instanceof Message ? $result : MessageBuilder::fromMessage($message)->setPayload($result)->build();
            $message->getHeaders()->getReplyChannel()->send($result);
        }
    }
}
