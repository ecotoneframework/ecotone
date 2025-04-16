<?php

namespace Ecotone\Modelling\MessageHandling\MetadataPropagator;

use Ecotone\Messaging\Attribute\PropagateHeaders;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\AggregateMessage;

/**
 * licence Apache-2.0
 */
class MessageHeadersPropagatorInterceptor
{
    public const GET_CURRENTLY_PROPAGATED_HEADERS_CHANNEL = 'ecotone.getCurrentlyPropagatedHeaders';
    public const ENABLE_POLLING_CONSUMER_PROPAGATION_CONTEXT = 'ecotone.enablePollingConsumerPropagation';
    public const DISABLE_POLLING_CONSUMER_PROPAGATION_CONTEXT = 'ecotone.disablePollingConsumerPropagation';
    public const IS_POLLING_CONSUMER_PROPAGATION_CONTEXT = 'ecotone.isPollingConsumerPropagation';
    private array $currentlyPropagatedHeaders = [];
    private bool $isPollingConsumer = false;

    public function storeHeaders(MethodInvocation $methodInvocation, Message $message, ?PropagateHeaders $propagateHeaders = null)
    {
        if ($propagateHeaders !== null && ! $propagateHeaders->doPropagation()) {
            $userlandHeaders = [];
        } else {
            $userlandHeaders = MessageHeaders::unsetAllFrameworkHeaders($message->getHeaders()->headers());
            unset(
                $userlandHeaders[AggregateMessage::AGGREGATE_ID],
                $userlandHeaders[AggregateMessage::CALLED_AGGREGATE_CLASS],
                $userlandHeaders[AggregateMessage::CALLED_AGGREGATE_INSTANCE],
                $userlandHeaders[AggregateMessage::TARGET_VERSION],
            );
            $userlandHeaders[MessageHeaders::MESSAGE_ID] = $message->getHeaders()->getMessageId();
            $userlandHeaders[MessageHeaders::MESSAGE_CORRELATION_ID] = $message->getHeaders()->getCorrelationId();
        }

        $this->currentlyPropagatedHeaders[] = $userlandHeaders;
        try {
            $reply = $methodInvocation->proceed();
        } finally {
            array_pop($this->currentlyPropagatedHeaders);
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

    /**
     * @return array<string, mixed>
     */
    #[ServiceActivator(self::GET_CURRENTLY_PROPAGATED_HEADERS_CHANNEL)]
    public function getLastHeaders(): array
    {
        $headers = end($this->currentlyPropagatedHeaders);

        if ($this->isCalledForFirstTime($headers)) {
            return [];
        }

        return $headers;
    }

    #[ServiceActivator(self::ENABLE_POLLING_CONSUMER_PROPAGATION_CONTEXT)]
    public function enablePollingConsumerPropagation(): void
    {
        $this->isPollingConsumer = true;
    }

    #[ServiceActivator(self::DISABLE_POLLING_CONSUMER_PROPAGATION_CONTEXT)]
    public function disablePollingConsumerPropagation(): void
    {
        $this->isPollingConsumer = false;
    }

    #[ServiceActivator(self::IS_POLLING_CONSUMER_PROPAGATION_CONTEXT)]
    public function isPollingConsumer(): bool
    {
        return $this->isPollingConsumer;
    }

    private function isCalledForFirstTime($headers): bool
    {
        return $headers === false;
    }
}
