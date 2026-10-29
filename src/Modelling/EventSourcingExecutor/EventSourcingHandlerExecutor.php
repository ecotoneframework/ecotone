<?php

namespace Ecotone\Modelling\EventSourcingExecutor;

use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Event;
use Ecotone\Modelling\EventSourcingHandlerMethod;

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
                Assert::isObject($eventPayload, 'Event returned by repository should be deserialized objects. Got: ' . Type::createFromVariable($eventPayload)->toString());
                $metadata  = $event->getMetadata();
            } else {
                Assert::isObject($event, 'Event returned by repository should be deserialized objects. Got: ' . Type::createFromVariable($event)->toString());
                $eventPayload = $event;
            }

            $message = MessageBuilder::withPayload($eventPayload)
                ->setMultipleHeaders($metadata)
                ->build();
            foreach ($this->eventSourcingHandlerMethods as $eventSourcingHandler) {
                if ($eventSourcingHandler->canHandle($eventPayload)) {
                    $this->aggregateMethodInvoker->executeMethod($aggregate, $eventSourcingHandler, $message);
                }
            }
        }

        return $aggregate;
    }
}
