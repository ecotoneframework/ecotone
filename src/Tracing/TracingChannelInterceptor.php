<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use function array_pop;

use Ecotone\Api\Messaging\BatchMessage;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageChannel;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\AsynchronousModule;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Config\Routing\BusRoutingMapBuilder;
use Throwable;

/**
 * licence Apache-2.0
 */
final class TracingChannelInterceptor implements ChannelInterceptor
{
    /** @var ActiveSpan[] */
    private array $openSendSpans = [];

    public function __construct(
        private string $channelName,
        private Tracer $tracer,
        private MessageBuilder $messageBuilder,
        private MessageSpanAttributes $messageSpanAttributes,
    ) {
    }

    public function preSend(Message $message, MessageChannel $messageChannel): ?Message
    {
        $attributes = [
            ...$this->messageSpanAttributes->from($message),
            SpanAttribute::MESSAGING_OPERATION_TYPE => SpanAttribute::OPERATION_SEND,
            SpanAttribute::MESSAGING_DESTINATION_NAME => $this->channelName,
        ];
        $isFirstChannel = self::deliveryChannelOf($message) === null;
        if ($isFirstChannel) {
            $attributes = [...$attributes, ...$this->deliveryAttributes($message)];
        }
        $attributes = [...$attributes, ...TracingInterceptor::trackingAttributesOf($this->tracer, $message, false)];
        $span = $this->tracer->startSpan(
            'send ' . $this->channelName,
            SpanKind::Producer,
            $attributes,
            $this->tracer->currentSpan() === null ? TracingInterceptor::remoteParentOf($message) : null,
        );
        $this->openSendSpans[] = $span;

        $carrier = TracingInterceptor::carrierOf($span);
        $sent = $this->messageBuilder->fromMessage($message)->setMultipleHeaders($carrier);
        if ($isFirstChannel) {
            $sent = $sent->setHeader(MessageHeaders::TRACING_DELIVERY, $this->channelName . ':' . $message->getHeaders()->getMessageId());
        }
        $payload = $message->getPayload();
        if ($payload instanceof BatchMessage) {
            $sent = $sent->setPayload(BatchMessage::fromEntries(array_map(
                fn (array $entry): array => [...$entry, 'headers' => [...($entry['headers'] ?? []), ...$carrier]],
                $payload->entries,
            )));
        }

        return $sent->build();
    }

    public static function deliveryChannelOf(Message $message): ?string
    {
        $headers = $message->getHeaders();
        if (! $headers->containsKey(MessageHeaders::TRACING_DELIVERY)) {
            return null;
        }
        [$channel, $messageId] = explode(':', (string) $headers->get(MessageHeaders::TRACING_DELIVERY), 2);

        return $messageId === $headers->getMessageId() ? $channel : null;
    }

    /**
     * @return array<string, scalar>
     */
    private function deliveryAttributes(Message $message): array
    {
        $attributes = [SpanAttribute::CHANNEL_NAME => $this->channelName];
        $payload = $message->getPayload();
        if ($payload instanceof BatchMessage) {
            $deliveries = [];
            foreach ($payload->entries as $entry) {
                $headers = $entry['headers'] ?? [];
                $deliveries[(string) ($headers[MessageHeaders::MESSAGE_ID] ?? '')] = self::targetEndpointIdOfRoutingSlip((string) ($headers[MessageHeaders::ROUTING_SLIP] ?? ''));
            }
            $attributes[SpanAttribute::BATCH_DELIVERIES] = json_encode($deliveries, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT);

            return $attributes;
        }
        $targetEndpointId = self::targetEndpointIdOf($message);
        if ($targetEndpointId !== null) {
            $attributes[SpanAttribute::ENDPOINT_ID] = $targetEndpointId;
        }

        return $attributes;
    }

    private static function targetEndpointIdOf(Message $message): ?string
    {
        $headers = $message->getHeaders();

        return $headers->containsKey(MessageHeaders::ROUTING_SLIP) ? self::targetEndpointIdOfRoutingSlip((string) $headers->get(MessageHeaders::ROUTING_SLIP)) : null;
    }

    private static function targetEndpointIdOfRoutingSlip(string $routingSlipHeader): ?string
    {
        if ($routingSlipHeader === '') {
            return null;
        }
        $routingSlip = explode(',', $routingSlipHeader);
        $executionChannel = $routingSlip[count($routingSlip) - 1];
        if (! str_ends_with($executionChannel, AsynchronousModule::HANDLER_EXECUTION_CHANNEL_SUFFIX)) {
            return null;
        }

        return BusRoutingMapBuilder::endpointIdOfDestinationChannel(substr($executionChannel, 0, -strlen(AsynchronousModule::HANDLER_EXECUTION_CHANNEL_SUFFIX)));
    }

    public function postSend(Message $message, MessageChannel $messageChannel): void
    {
    }

    public function afterSendCompletion(Message $message, MessageChannel $messageChannel, ?Throwable $exception): bool
    {
        $span = array_pop($this->openSendSpans);
        if ($exception !== null) {
            $span?->fail($exception);
        } else {
            $span?->end();
        }

        return false;
    }

    public function preReceive(MessageChannel $messageChannel): bool
    {
        return true;
    }

    public function afterReceiveCompletion(?Message $message, MessageChannel $messageChannel, ?Throwable $exception): void
    {
        if ($exception === null || $message === null) {
            return;
        }

        $this->tracer
            ->startSpan('receive ' . $this->channelName, SpanKind::Consumer, [
                ...$this->messageSpanAttributes->from($message),
                SpanAttribute::MESSAGING_OPERATION_TYPE => SpanAttribute::OPERATION_PROCESS,
                SpanAttribute::MESSAGING_DESTINATION_NAME => $this->channelName,
                SpanAttribute::CHANNEL_NAME => $this->channelName,
                ...TracingInterceptor::trackingAttributesCarriedBy($message, false),
            ], TracingInterceptor::remoteParentOf($message))
            ->fail($exception);
    }

    public function postReceive(Message $message, MessageChannel $messageChannel): ?Message
    {
        return $message;
    }
}
