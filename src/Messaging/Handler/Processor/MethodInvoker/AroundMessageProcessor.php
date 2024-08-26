<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Message;

/**
 * @licence Apache-2.0
 */
class AroundMessageProcessor implements MessageProcessor
{
    /**
     * @param AroundMethodInterceptor[] $aroundInterceptors
     */
    public function __construct(
        private AroundInterceptable $messageProcessor,
        private ResultToMessageConverter $resultToMessageConverter,
        private array $aroundInterceptors,
    ) {
    }

    public function process(Message $message): ?Message
    {
        $invocation = new AroundMethodInvocation(
            $message,
            $this->aroundInterceptors,
            $this->messageProcessor,
        );

        return $this->resultToMessageConverter->convertToMessage(
            $message,
            $invocation->proceed(),
        );
    }
}
