<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\PublishEvents;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Metadata\RevisionMetadataEnricher;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Attribute\NamedEvent;
use Ecotone\Modelling\Event;
use Ecotone\Modelling\EventBus;
use Ramsey\Uuid\Uuid;

final class PublishAggregateEventsService
{
    public function __construct(private string $calledInterface, private EventBus $eventBus)
    {
    }

    public function publish(Message $message, array $metadata): ?Message
    {
        $resultAggregateEvents = $message->getHeaders()->containsKey(AggregateMessage::RESULT_AGGREGATE_EVENTS) ? $message->getHeaders()->get(AggregateMessage::RESULT_AGGREGATE_EVENTS) : [];
        $calledAggregateEvents = $message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_EVENTS) ? $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_EVENTS) : [];

        $this->publishEvents($this->resolveEvents($resultAggregateEvents, $message, $metadata, $this->calledInterface));
        $this->publishEvents($this->resolveEvents($calledAggregateEvents, $message, $metadata, $this->calledInterface));

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

    // fixme duplicated
    public function resolveEvents(array $events, Message $message, array $metadata, string $calledInterface): array
    {
        Assert::isIterable($events, "Return value Event Sourced Aggregate {$calledInterface} must return array of events");

        return array_map(static function ($event) use ($message, $metadata, $calledInterface): Event {
            if (! is_object($event)) {
                $typeDescriptor = TypeDescriptor::createFromVariable($event);
                throw InvalidArgumentException::create("Events return by after calling {$calledInterface} must all be objects, {$typeDescriptor->toString()} given");
            }
            if ($event instanceof Event) {
                $metadata = $event->getMetadata();
                $event = $event->getPayload();
            }

            $metadata = MessageHeaders::unsetAllFrameworkHeaders($metadata);
            $metadata = RevisionMetadataEnricher::enrich($metadata, $event);
            $metadata[MessageHeaders::MESSAGE_ID] ??= Uuid::uuid4()->toString();
            $metadata[MessageHeaders::TIMESTAMP] ??= (int)round(microtime(true));
            $metadata = MessageHeaders::propagateContextHeaders([
                MessageHeaders::MESSAGE_ID => $message->getHeaders()->getMessageId(),
                MessageHeaders::MESSAGE_CORRELATION_ID => $message->getHeaders()->getCorrelationId(),
            ], $metadata);

            return Event::create($event, $metadata);
        }, $events);
    }
}
