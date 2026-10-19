<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use function array_filter;
use function array_pop;
use function array_reverse;

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
    /** @var list<list<ActiveSpan>> */
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
        $payload = $message->getPayload();
        if ($payload instanceof BatchMessage && $this->tracer->currentSpan() === null) {
            return $this->relayBatch($message, $payload);
        }

        $isFirstChannel = self::deliveryChannelOf($message) === null;
        $span = $this->startSendSpan($message, $isFirstChannel ? $this->deliveryAttributes($message) : [], $this->tracer->currentSpan() === null);
        $this->openSendSpans[] = [$span];

        $carrier = TracingInterceptor::carrierOf($span);
        $sent = $this->messageBuilder->fromMessage($message)->setMultipleHeaders($carrier);
        if ($isFirstChannel) {
            $sent = $sent->setHeader(MessageHeaders::TRACING_DELIVERY, $this->channelName . ':' . $message->getHeaders()->getMessageId());
        }
        if ($payload instanceof BatchMessage) {
            $sent = $sent->setPayload(BatchMessage::fromEntries(array_map(
                fn (array $entry): array => [...$entry, 'headers' => [...($entry['headers'] ?? []), ...$carrier]],
                $payload->entries,
            )));
        }

        return $sent->build();
    }

    private function relayBatch(Message $message, BatchMessage $batch): Message
    {
        $entriesByParent = [];
        $firstEntryOfParent = [];
        foreach ($batch->entries as $index => $entry) {
            $entryMessage = $this->messageBuilder->withPayload('')->setMultipleHeaders($entry['headers'] ?? [])->build();
            $parent = TracingInterceptor::remoteParentOf($entryMessage);
            $parentKey = $parent === null ? '' : $parent->traceId . '-' . $parent->spanId;
            $entriesByParent[$parentKey][$index] = $entry;
            $firstEntryOfParent[$parentKey] ??= $entryMessage;
        }

        $spans = [];
        $entries = $batch->entries;
        foreach ($entriesByParent as $parentKey => $traceEntries) {
            $firstEntry = $firstEntryOfParent[$parentKey];
            $firstDeliveries = array_filter($traceEntries, fn (array $entry): bool => ! self::isDeliveredAlready($entry['headers'] ?? []));
            $span = $this->startSendSpan($firstEntry, $firstDeliveries === [] ? [] : [SpanAttribute::CHANNEL_NAME => $this->channelName, SpanAttribute::BATCH_DELIVERIES => self::batchDeliveriesOf($firstDeliveries)], true);
            $spans[] = $span;
            $carrier = TracingInterceptor::carrierOf($span);
            foreach ($traceEntries as $index => $entry) {
                $entries[$index] = [...$entry, 'headers' => [...($entry['headers'] ?? []), ...$carrier]];
            }
        }
        $this->openSendSpans[] = $spans;

        $sent = $this->messageBuilder->fromMessage($message)->setPayload(BatchMessage::fromEntries(array_values($entries)));
        if ($spans !== []) {
            $sent = $sent->setMultipleHeaders(TracingInterceptor::carrierOf($spans[0]));
        }

        return $sent->setHeader(MessageHeaders::TRACING_DELIVERY, $this->channelName . ':' . $message->getHeaders()->getMessageId())->build();
    }

    /**
     * @param array<string, scalar> $deliveryAttributes
     */
    private function startSendSpan(Message $message, array $deliveryAttributes, bool $continuesTheMessageTrace): ActiveSpan
    {
        $name = 'send ' . $this->channelName;
        $attributes = [
            ...$this->messageSpanAttributes->from($message),
            SpanAttribute::MESSAGING_OPERATION_TYPE => SpanAttribute::OPERATION_SEND,
            SpanAttribute::MESSAGING_DESTINATION_NAME => $this->channelName,
            ...$deliveryAttributes,
            ...($continuesTheMessageTrace ? TracingInterceptor::trackingAttributesCarriedBy($message, false) : TracingInterceptor::trackingAttributesOf($this->tracer, $message, false)),
        ];
        if (! $continuesTheMessageTrace) {
            return $this->tracer->startSpan($name, SpanKind::Producer, $attributes);
        }
        $remoteParent = TracingInterceptor::remoteParentOf($message);

        return $remoteParent === null
            ? $this->tracer->startRootSpan($name, SpanKind::Producer, $attributes)
            : $this->tracer->startSpan($name, SpanKind::Producer, $attributes, $remoteParent);
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
            $attributes[SpanAttribute::BATCH_DELIVERIES] = self::batchDeliveriesOf($payload->entries);

            return $attributes;
        }
        $targetEndpointId = self::targetEndpointIdOf($message);
        if ($targetEndpointId !== null) {
            $attributes[SpanAttribute::ENDPOINT_ID] = $targetEndpointId;
        }

        return $attributes;
    }

    /**
     * @param array<string, mixed> $headers
     */
    private static function isDeliveredAlready(array $headers): bool
    {
        $delivery = (string) ($headers[MessageHeaders::TRACING_DELIVERY] ?? '');

        return $delivery !== '' && str_ends_with($delivery, ':' . ($headers[MessageHeaders::MESSAGE_ID] ?? ''));
    }

    /**
     * @param array<int, array{payload: mixed, headers?: array<string, mixed>}> $entries
     */
    private static function batchDeliveriesOf(array $entries): string
    {
        $deliveries = [];
        foreach ($entries as $entry) {
            $headers = $entry['headers'] ?? [];
            $deliveries[(string) ($headers[MessageHeaders::MESSAGE_ID] ?? '')] = self::targetEndpointIdOfRoutingSlip((string) ($headers[MessageHeaders::ROUTING_SLIP] ?? ''));
        }

        return json_encode($deliveries, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT);
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
        foreach (array_reverse(array_pop($this->openSendSpans) ?? []) as $span) {
            if ($exception !== null) {
                $span->fail($exception);
            } else {
                $span->end();
            }
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
