<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\PublishEvents;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\SaveAggregateServiceTemplate;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Attribute\NamedEvent;
use Ecotone\Modelling\EventBus;

/**
 * licence Apache-2.0
 */
final class PublishAggregateEventsService implements MessageProcessor
{
    public function __construct(private string $calledInterface, private EventBus $eventBus)
    {
    }

    public function process(Message $message): ?Message
    {
        $resultAggregateEvents = $message->getHeaders()->containsKey(AggregateMessage::RESULT_AGGREGATE_EVENTS) ? $message->getHeaders()->get(AggregateMessage::RESULT_AGGREGATE_EVENTS) : [];
        $calledAggregateEvents = $message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_EVENTS) ? $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_EVENTS) : [];

        $this->publishEvents(SaveAggregateServiceTemplate::buildEcotoneEvents($calledAggregateEvents, $this->calledInterface, $message));
        $this->publishEvents(SaveAggregateServiceTemplate::buildEcotoneEvents($resultAggregateEvents, $this->calledInterface, $message));

        $isExecutionResultNull = $message->getHeaders()->containsKey(AggregateMessage::NULL_EXECUTION_RESULT) ? $message->getHeaders()->get(AggregateMessage::NULL_EXECUTION_RESULT) : false;
        if ($isExecutionResultNull) {
            return null;
        }

        return MessageBuilder::fromMessage($message)->build();
    }

    private function publishEvents(array $events): void
    {
        foreach ($events as $event) {
            $this->eventBus->publish($event->getPayload(), $event->getMetadata());

            $eventDefinition = ClassDefinition::createFor(TypeDescriptor::createFromVariable($event->getPayload()));
            $namedEvent = TypeDescriptor::create(NamedEvent::class);
            if ($eventDefinition->hasClassAnnotation($namedEvent)) {
                /** @var NamedEvent $namedEvent */
                $namedEvent = $eventDefinition->getSingleClassAnnotation($namedEvent);

                $this->eventBus->publishWithRouting($namedEvent->getName(), $event->getPayload(), MediaType::APPLICATION_X_PHP, $event->getMetadata());
            }
        }
    }
}
