<?php

namespace Ecotone\Modelling\EventSourcingExecutor;

use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Event;
use Ecotone\Modelling\EventSourcingHandlerMethod;
use Ecotone\Modelling\SnapshotEvent;

/**
 * licence Apache-2.0
 */
final class EventSourcingHandlerExecutor
{
    /**
     * @param EventSourcingHandlerMethod[] $eventSourcingHandlerMethods
     */
    public function __construct(
        private string $aggregateClassName,
        private array $eventSourcingHandlerMethods,
        private AggregateMethodInvoker $aggregateMethodInvoker,
    ) {
    }

    /**
     * @param Event[] $events
     */
    public function fill(array $events, ?object $existingAggregate): object
    {
        $aggregate = $existingAggregate ?? (new $this->aggregateClassName());
        foreach ($events as $event) {
            $eventPayload = null;
            $metadata = [];
            if ($event instanceof Event) {
                $eventPayload = $event->getPayload();
                $eventType = TypeDescriptor::createFromVariable($eventPayload);
                $metadata  = $event->getMetadata();
            } else {
                $eventType = TypeDescriptor::createFromVariable($event);
                $eventPayload = $event;
            }

            if ($eventType->toString() === SnapshotEvent::class) {
                $aggregate = $eventPayload->getAggregate();

                continue;
            }

            $message = MessageBuilder::withPayload($eventPayload)
                ->setMultipleHeaders($metadata)
                ->build();
            foreach ($this->eventSourcingHandlerMethods as $eventSourcingHandler) {
                if ($eventSourcingHandler->canHandle($eventType)) {
                    $this->aggregateMethodInvoker->executeMethod($aggregate, $eventSourcingHandler, $message);
                }
            }
        }

        return $aggregate;
    }
}
