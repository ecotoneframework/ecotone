<?php

declare(strict_types=1);

namespace Ecotone\Tracing;

use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Modelling\Config\Routing\BusRoutingMapBuilder;
use Ecotone\Projecting\Config\ProjectingModule;
use Throwable;

/**
 * licence Apache-2.0
 */
final class TracedRouteProcessor implements MessageProcessor
{
    public function __construct(
        private string $routeName,
        private MessageProcessor $routeProcessor,
        private Tracer $tracer,
        private MessageSpanAttributes $messageSpanAttributes,
    ) {
    }

    public function process(Message $message): ?Message
    {
        $span = $this->tracer->startSpan($this->spanName(), SpanKind::Producer, [
            ...$this->messageSpanAttributes->from($message),
            SpanAttribute::MESSAGING_OPERATION_TYPE => SpanAttribute::OPERATION_SEND,
            SpanAttribute::MESSAGING_DESTINATION_NAME => $this->routeName,
            ...$this->destinationAttributes(),
            ...TracingInterceptor::trackingAttributesOf($this->tracer, $message, false),
        ]);

        try {
            $result = $this->routeProcessor->process($message);
        } catch (Throwable $failure) {
            $span->fail($failure);

            throw $failure;
        }

        $span->end();

        return $result;
    }

    private function spanName(): string
    {
        $projectionName = ProjectingModule::projectionNameOfTriggerChannel($this->routeName);
        if ($projectionName !== null) {
            return 'route ' . $projectionName;
        }

        return 'route ' . (BusRoutingMapBuilder::endpointIdOfDestinationChannel($this->routeName) ?? $this->routeName);
    }

    /**
     * @return array<string, string>
     */
    private function destinationAttributes(): array
    {
        $projectionName = ProjectingModule::projectionNameOfTriggerChannel($this->routeName);
        if ($projectionName !== null) {
            return [SpanAttribute::PROJECTION_NAME => $projectionName];
        }
        $endpointId = BusRoutingMapBuilder::endpointIdOfDestinationChannel($this->routeName);

        return $endpointId === null ? [] : [SpanAttribute::ENDPOINT_ID => $endpointId];
    }
}
