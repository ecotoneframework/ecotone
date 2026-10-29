<?php

namespace Ecotone\Messaging\Handler\Processor;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\ResultToMessageConverter;
use Ecotone\Messaging\Message;

/**
 * licence Apache-2.0
 */
class MethodInvokerProcessor implements MessageProcessor
{
    public function __construct(
        private MethodInvoker $methodInvoker,
        private ResultToMessageConverter $resultToMessageBuilder,
    ) {
    }

    public function process(Message $message): ?Message
    {
        return $this->resultToMessageBuilder->convertToMessage(
            $message,
            $this->methodInvoker->execute($message)
        );
    }
}
