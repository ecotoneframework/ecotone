<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanContext;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Config\MessageBusChannel;
use Ecotone\Projecting\Config\ProjectingModule;
use Ecotone\Projecting\ProjectionOperationEndpoint;
use Ecotone\Projecting\ProjectionTriggerEndpoint;
use Throwable;

/**
 * licence Apache-2.0
 */
final class TracingInterceptor
{
    public const DISTRIBUTED_SEND_SPAN = 'send distributed';

    /**
     * @param array<string, string> $endpointIdsByHandler
     */
    public function __construct(
        private Tracer $tracer,
        private MessageBuilder $messageBuilder,
        private MessageSpanAttributes $messageSpanAttributes,
        private array $endpointIdsByHandler,
    ) {
    }

    public function traceCommandBus(MethodInvocation $methodInvocation, Message $message): mixed
    {
        return $this->traceEntry('send command', $methodInvocation, $message, SpanKind::Server, [
            SpanAttribute::MESSAGING_OPERATION_TYPE => SpanAttribute::OPERATION_SEND,
            SpanAttribute::MESSAGING_DESTINATION_NAME => self::busDestinationOf($message, MessageBusChannel::COMMAND_CHANNEL_NAME_BY_NAME),
        ]);
    }

    public function traceEventBus(MethodInvocation $methodInvocation, Message $message): mixed
    {
        return $this->traceEntry('send event', $methodInvocation, $message, SpanKind::Server, [
            SpanAttribute::MESSAGING_OPERATION_TYPE => SpanAttribute::OPERATION_SEND,
            SpanAttribute::MESSAGING_DESTINATION_NAME => self::busDestinationOf($message, MessageBusChannel::EVENT_CHANNEL_NAME_BY_NAME),
        ]);
    }

    public function traceQueryBus(MethodInvocation $methodInvocation, Message $message): mixed
    {
        return $this->traceEntry('send query', $methodInvocation, $message, SpanKind::Server, [
            SpanAttribute::MESSAGING_OPERATION_TYPE => SpanAttribute::OPERATION_SEND,
            SpanAttribute::MESSAGING_DESTINATION_NAME => self::busDestinationOf($message, MessageBusChannel::QUERY_CHANNEL_NAME_BY_NAME),
        ]);
    }

    public function traceCommandHandler(MethodInvocation $methodInvocation, Message $message, CommandHandler $endpoint): mixed
    {
        return $this->traceHandler($methodInvocation, $message, $this->endpointIdOf($methodInvocation, $endpoint->getEndpointId()));
    }

    public function traceQueryHandler(MethodInvocation $methodInvocation, Message $message, QueryHandler $endpoint): mixed
    {
        return $this->traceHandler($methodInvocation, $message, $this->endpointIdOf($methodInvocation, $endpoint->getEndpointId()));
    }

    public function traceEventHandler(MethodInvocation $methodInvocation, Message $message, EventHandler $endpoint): mixed
    {
        return $this->traceHandler($methodInvocation, $message, $this->endpointIdOf($methodInvocation, $endpoint->getEndpointId()));
    }

    public function traceMessageHandler(MethodInvocation $methodInvocation, Message $message, InternalHandler $endpoint): mixed
    {
        return $this->traceHandler($methodInvocation, $message, $this->endpointIdOf($methodInvocation, $endpoint->getEndpointId()));
    }

    public function traceAsynchronousEndpoint(MethodInvocation $methodInvocation, Message $message): mixed
    {
        $headers = $message->getHeaders();
        $name = match (true) {
            $headers->containsKey(MessageHeaders::POLLED_CHANNEL_NAME) => 'receive ' . $headers->get(MessageHeaders::POLLED_CHANNEL_NAME),
            $headers->containsKey(MessageHeaders::INBOUND_REQUEST_CHANNEL) && ! self::isPollOnlyChannel((string) $headers->get(MessageHeaders::INBOUND_REQUEST_CHANNEL)) => 'receive ' . $headers->get(MessageHeaders::INBOUND_REQUEST_CHANNEL),
            default => 'poll ' . $headers->get(MessageHeaders::CONSUMER_POLLING_METADATA)->getEndpointId(),
        };

        $attributes = [SpanAttribute::MESSAGING_OPERATION_TYPE => SpanAttribute::OPERATION_PROCESS];
        if ($headers->containsKey(MessageHeaders::POLLED_CHANNEL_NAME)) {
            $attributes[SpanAttribute::MESSAGING_DESTINATION_NAME] = $headers->get(MessageHeaders::POLLED_CHANNEL_NAME);
            $attributes[SpanAttribute::CHANNEL_NAME] = TracingChannelInterceptor::deliveryChannelOf($message) ?? $headers->get(MessageHeaders::POLLED_CHANNEL_NAME);
        }

        return $this->trace($name, $methodInvocation, $message, SpanKind::Consumer, $attributes);
    }

    public function traceProjection(MethodInvocation $methodInvocation, Message $message, ProjectionTriggerEndpoint $trigger): mixed
    {
        return $this->trace('process ' . $trigger->projectionName, $methodInvocation, $message, SpanKind::Consumer, [
            SpanAttribute::MESSAGING_OPERATION_TYPE => SpanAttribute::OPERATION_PROCESS,
            SpanAttribute::PROJECTION_NAME => $trigger->projectionName,
        ]);
    }

    public function traceProjectionBatch(MethodInvocation $methodInvocation, Message $message): mixed
    {
        $projectionName = (string) $message->getPayload();

        return $this->trace('process partition batch ' . $projectionName, $methodInvocation, $message, SpanKind::Internal, [
            SpanAttribute::MESSAGING_OPERATION_TYPE => SpanAttribute::OPERATION_PROCESS,
            SpanAttribute::PROJECTION_NAME => $projectionName,
        ]);
    }

    public function traceProjectionOperation(MethodInvocation $methodInvocation, Message $message, ProjectionOperationEndpoint $endpoint): mixed
    {
        return $this->traceEntry($endpoint->operation . ' ' . $endpoint->projectionName, $methodInvocation, $message, SpanKind::Server, [
            SpanAttribute::MESSAGING_OPERATION_TYPE => SpanAttribute::OPERATION_PROCESS,
            SpanAttribute::PROJECTION_NAME => $endpoint->projectionName,
            SpanAttribute::PROJECTION_OPERATION => $endpoint->operation,
        ]);
    }

    public function traceMessageGateway(MethodInvocation $methodInvocation, Message $message, MessageGateway $gateway): mixed
    {
        return $this->traceEntry('send ' . $gateway->requestChannel, $methodInvocation, $message, SpanKind::Server, [
            SpanAttribute::MESSAGING_OPERATION_TYPE => SpanAttribute::OPERATION_SEND,
            SpanAttribute::MESSAGING_DESTINATION_NAME => $gateway->requestChannel,
        ]);
    }

    public function traceOrchestratorGateway(MethodInvocation $methodInvocation, Message $message): mixed
    {
        return $this->traceEntry('send orchestrator', $methodInvocation, $message, SpanKind::Server, [SpanAttribute::MESSAGING_OPERATION_TYPE => SpanAttribute::OPERATION_SEND]);
    }

    public function traceDistributedBus(MethodInvocation $methodInvocation, Message $message): mixed
    {
        return $this->traceEntry(self::DISTRIBUTED_SEND_SPAN, $methodInvocation, $message, SpanKind::Producer, [SpanAttribute::MESSAGING_OPERATION_TYPE => SpanAttribute::OPERATION_SEND]);
    }

    public function provideContextForDistributedBus(Message $message): Message
    {
        $currentSpan = $this->tracer->currentSpan();
        if ($currentSpan === null) {
            return $message;
        }

        return $this->messageBuilder->fromMessage($message)
            ->setMultipleHeaders(self::carrierOf($currentSpan))
            ->build();
    }

    /**
     * @return array<string, string>
     */
    public static function carrierOf(ActiveSpan $span): array
    {
        $carrier = [MessageHeaders::TRACEPARENT => $span->context()->toTraceparent()];
        $traceState = match (true) {
            (bool) ($span->attributes()[SpanAttribute::TRACE_UNTRACKED] ?? false) => TraceState::withUntracked($span->context()->traceState),
            (bool) ($span->attributes()[SpanAttribute::TRACE_CARRIED] ?? false) => TraceState::withCarried($span->context()->traceState),
            default => $span->context()->traceState,
        };
        if ($traceState !== '') {
            $carrier[MessageHeaders::TRACESTATE] = $traceState;
        }

        return $carrier;
    }

    /**
     * @return array<string, true>
     */
    public static function trackingAttributesOf(Tracer $tracer, Message $message, bool $startsTheTraceHere): array
    {
        $current = $tracer->currentSpan();
        if ($current === null) {
            return self::trackingAttributesCarriedBy($message, $startsTheTraceHere);
        }

        return array_filter([
            SpanAttribute::TRACE_CARRIED => (bool) ($current->attributes()[SpanAttribute::TRACE_CARRIED] ?? false),
            SpanAttribute::TRACE_UNTRACKED => (bool) ($current->attributes()[SpanAttribute::TRACE_UNTRACKED] ?? false),
        ]);
    }

    /**
     * @return array<string, true>
     */
    public static function trackingAttributesCarriedBy(Message $message, bool $startsTheTraceHere): array
    {
        if (TraceState::isUntracked($message)) {
            return [SpanAttribute::TRACE_UNTRACKED => true];
        }
        $carried = $startsTheTraceHere ? self::remoteParentOf($message) !== null : TraceState::isCarried($message);

        return $carried ? [SpanAttribute::TRACE_CARRIED => true] : [];
    }

    public static function remoteParentOf(Message $message): ?SpanContext
    {
        $headers = $message->getHeaders();
        $traceparent = SpanContext::fromTraceparent(
            $headers->containsKey(MessageHeaders::TRACEPARENT) ? $headers->get(MessageHeaders::TRACEPARENT) : null,
            $headers->containsKey(MessageHeaders::TRACESTATE) ? (string) $headers->get(MessageHeaders::TRACESTATE) : '',
        );
        if ($traceparent !== null || ! $headers->containsKey(MessageHeaders::TRACING_ID)) {
            return $traceparent;
        }
        $tracingId = TracingId::normalizedOrNull((string) $headers->get(MessageHeaders::TRACING_ID));

        return $tracingId === null ? null : new SpanContext($tracingId, bin2hex(random_bytes(8)));
    }

    private static function entryParentOf(Message $message): ?SpanContext
    {
        $headers = $message->getHeaders();
        if (! $headers->containsKey(MessageHeaders::TRACING_ID)) {
            return self::remoteParentOf($message);
        }
        if ($headers->containsKey(MessageHeaders::TRACEPARENT)) {
            throw InvalidArgumentException::create('The message carries both tracingId and traceparent, so it is not clear which trace it belongs to. Pass tracingId to track the flow with TracingGateway, or traceparent when the trace comes from OpenTelemetry instrumentation, not both.');
        }

        return new SpanContext(TracingId::normalize((string) $headers->get(MessageHeaders::TRACING_ID)), bin2hex(random_bytes(8)));
    }

    private function endpointIdOf(MethodInvocation $methodInvocation, string $declaredEndpointId): string
    {
        $object = $methodInvocation->getObjectToInvokeOn();
        $class = is_string($object) ? $object : $object::class;
        while ($class !== false) {
            $endpointId = $this->endpointIdsByHandler[$class . '::' . $methodInvocation->getMethodName()] ?? null;
            if ($endpointId !== null) {
                return $endpointId;
            }
            $class = get_parent_class($class);
        }

        return $declaredEndpointId;
    }

    private function traceHandler(MethodInvocation $methodInvocation, Message $message, string $endpointId): mixed
    {
        $attributes = [
            SpanAttribute::MESSAGING_OPERATION_TYPE => SpanAttribute::OPERATION_PROCESS,
            SpanAttribute::CODE_FUNCTION_NAME => $methodInvocation->getName(),
        ];
        if ($endpointId !== '') {
            $attributes[SpanAttribute::ENDPOINT_ID] = $endpointId;
        }

        return $this->traceUnitOfWorkStep('process ' . self::shortNameOf($methodInvocation->getName()), $methodInvocation, $message, $attributes);
    }

    /**
     * @param array<string, scalar> $attributes
     */
    private function traceUnitOfWorkStep(string $name, MethodInvocation $methodInvocation, Message $message, array $attributes): mixed
    {
        try {
            $result = $this->trace($name, $methodInvocation, $message, SpanKind::Server, $attributes);
        } catch (Throwable $failure) {
            $this->tracer->currentSpan()?->markUnitOfWorkFailed();

            throw $failure;
        }
        $this->tracer->currentSpan()?->markUnitOfWorkRecovered();

        return $result;
    }

    private static function isPollOnlyChannel(string $inboundRequestChannel): bool
    {
        return $inboundRequestChannel === NullableMessageChannel::CHANNEL_NAME || ProjectingModule::projectionNameOfTriggerChannel($inboundRequestChannel) !== null;
    }

    private static function busDestinationOf(Message $message, string $routingKeyHeader): string
    {
        $headers = $message->getHeaders();
        if ($headers->containsKey($routingKeyHeader)) {
            return (string) $headers->get($routingKeyHeader);
        }
        $payload = $message->getPayload();

        return is_object($payload) ? $payload::class : get_debug_type($payload);
    }

    private static function shortNameOf(string $classAndMethod): string
    {
        [$class, $method] = explode('::', $classAndMethod, 2);
        $class = explode("\0", $class, 2)[0];

        return substr($class, (int) strrpos('\\' . $class, '\\')) . '::' . $method;
    }

    /**
     * @param array<string, scalar> $attributes
     */
    private function traceEntry(string $name, MethodInvocation $methodInvocation, Message $message, SpanKind $kind, array $attributes): mixed
    {
        $currentSpan = $this->tracer->currentSpan();
        $headers = $message->getHeaders();
        if ($currentSpan !== null && $headers->containsKey(MessageHeaders::TRACING_ID) && TracingId::normalizedOrNull((string) $headers->get(MessageHeaders::TRACING_ID)) !== $currentSpan->context()->traceId) {
            $flow = $currentSpan->context()->traceId;

            throw InvalidArgumentException::create("tracingId names the flow a message starts, but this message is sent from inside flow {$flow}, so it belongs to that flow already. Await {$flow} with TracingGateway, or send the message from outside a handler, where a flow starts.");
        }

        return $this->trace($name, $methodInvocation, $message, $kind, $attributes);
    }

    /**
     * @param array<string, scalar> $attributes
     */
    private function trace(string $name, MethodInvocation $methodInvocation, Message $message, SpanKind $kind, array $attributes): mixed
    {
        $isLocalRoot = $this->tracer->currentSpan() === null;
        $remoteParent = ! $isLocalRoot ? null : ($kind === SpanKind::Consumer ? self::remoteParentOf($message) : self::entryParentOf($message));
        if ($isLocalRoot && ($kind !== SpanKind::Consumer || $remoteParent === null)) {
            $attributes[SpanAttribute::TRACE_ROOT] = true;
        }
        $attributes = [...$attributes, ...self::trackingAttributesOf($this->tracer, $message, $isLocalRoot && $kind !== SpanKind::Consumer)];
        $span = $this->tracer->startSpan($name, $kind, [...$this->messageSpanAttributes->from($message), ...$attributes], $remoteParent);

        try {
            $result = $methodInvocation->proceed();
        } catch (Throwable $failure) {
            $span->fail($failure);

            throw $failure;
        }

        $span->end();

        return $result;
    }
}
