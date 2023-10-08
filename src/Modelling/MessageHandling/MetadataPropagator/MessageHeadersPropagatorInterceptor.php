<?php

namespace Ecotone\Modelling\MessageHandling\MetadataPropagator;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;

class MessageHeadersPropagatorInterceptor
{
    private array $currentlyPropagatedHeaders = [];

    public function storeHeaders(MethodInvocation $methodInvocation, Message $message)
    {
        $userlandHeaders = MessageHeaders::unsetAllFrameworkHeaders($message->getHeaders()->headers());
        $userlandHeaders[MessageHeaders::MESSAGE_ID] = $message->getHeaders()->getMessageId();
        $userlandHeaders[MessageHeaders::MESSAGE_CORRELATION_ID] = $message->getHeaders()->getCorrelationId();

        $this->currentlyPropagatedHeaders[] = $userlandHeaders;

        try {
            $reply = $methodInvocation->proceed();
        } finally {
            array_shift($this->currentlyPropagatedHeaders);
        }

        return $reply;
    }

    public function propagateHeaders(array $headers): array
    {
        if (array_key_exists(MessageHeaders::STREAM_BASED_SOURCED, $headers) && $headers[MessageHeaders::STREAM_BASED_SOURCED]) {
            return $headers;
        }

        return MessageHeaders::propagateContextHeaders($this->getLastHeaders(), $headers);
    }

    public function getLastHeaders(): array
    {
        $headers = end($this->currentlyPropagatedHeaders);

        if ($this->isCalledForFirstTime($headers)) {
            return [];
        }

        return $headers;
    }

    private function isCalledForFirstTime($headers): bool
    {
        return $headers === false;
    }
}
