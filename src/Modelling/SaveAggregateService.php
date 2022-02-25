<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\NonProxyGateway;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Attribute\NamedEvent;
use Ecotone\Modelling\Config\BusModule;

/**
 * Class SaveAggregateService
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SaveAggregateService
{
    private StandardRepository|EventSourcedRepository $aggregateRepository;
    private PropertyReaderAccessor $propertyReaderAccessor;
    /**
     * @var NonProxyGateway|EventBus
     */
    private object $objectEventBus;
    /**
     * @var NonProxyGateway|EventBus
     */
    private NonProxyGateway $namedEventBus;
    private ?string $aggregateMethodWithEvents;
    private PropertyEditorAccessor $propertyEditorAccessor;
    private array $aggregateIdentifierMapping;
    private InterfaceToCall|string $aggregateInterface;
    private ?string $aggregateVersionProperty;
    private bool $isAggregateVersionAutomaticallyIncreased;
    private bool $isEventSourced;
    private bool $isFactoryMethod;

    public function __construct(InterfaceToCall|string $aggregateInterface, bool $isFactoryMethod, bool $isEventSourced, StandardRepository|EventSourcedRepository $aggregateRepository, PropertyEditorAccessor $propertyEditorAccessor, PropertyReaderAccessor $propertyReaderAccessor, NonProxyGateway $objectEventBus, NonProxyGateway $namedEventBus, ?string $aggregateMethodWithEvents, array $aggregateIdentifierMapping, ?string $aggregateVersionProperty, bool $isAggregateVersionAutomaticallyIncreased)
    {
        $this->aggregateRepository = $aggregateRepository;
        $this->propertyReaderAccessor = $propertyReaderAccessor;
        $this->objectEventBus = $objectEventBus;
        $this->propertyEditorAccessor = $propertyEditorAccessor;
        $this->aggregateMethodWithEvents = $aggregateMethodWithEvents;
        $this->aggregateIdentifierMapping = $aggregateIdentifierMapping;
        $this->aggregateInterface = $aggregateInterface;
        $this->aggregateVersionProperty = $aggregateVersionProperty;
        $this->isAggregateVersionAutomaticallyIncreased = $isAggregateVersionAutomaticallyIncreased;
        $this->isEventSourced = $isEventSourced;
        $this->namedEventBus = $namedEventBus;
        $this->isFactoryMethod = $isFactoryMethod;
    }

    public function save(Message $message, array $metadata) : \Ecotone\Messaging\Message
    {
        $aggregate = $message->getHeaders()->get(AggregateMessage::AGGREGATE_OBJECT);
        $events = [];

        if ($this->isEventSourced) {
            $events = $message->getPayload();
            Assert::isIterable($events, "Return value Event Sourced Aggregate {$this->aggregateInterface} must return iterable events");
        }elseif ($this->aggregateMethodWithEvents) {
            $events = call_user_func([$aggregate, $this->aggregateMethodWithEvents]);
        }
        foreach ($events as $event) {
            if (!is_object($event)) {
                $typeDescriptor = TypeDescriptor::createFromVariable($event);
                throw InvalidArgumentException::create("Events return by after calling {$this->aggregateInterface} must all be objects, {$typeDescriptor->toString()} given");
            }
        }

        $versionBeforeHandling = $message->getHeaders()->containsKey(AggregateMessage::TARGET_VERSION)
            ? $message->getHeaders()->get(AggregateMessage::TARGET_VERSION)
            : null;

        if ($this->aggregateVersionProperty && $this->isAggregateVersionAutomaticallyIncreased && !$this->isEventSourced) {
            $this->propertyEditorAccessor->enrichDataWith(
                PropertyPath::createWith($this->aggregateVersionProperty),
                $aggregate,
                $versionBeforeHandling + 1,
                $message,
                null
            );
        }

        $aggregateIds = $message->getHeaders()->containsKey(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER)
                            ? $message->getHeaders()->get(AggregateMessage::AGGREGATE_ID)
                            : [];
        if (!$aggregateIds) {
            foreach ($this->aggregateIdentifierMapping as $aggregateIdName => $aggregateIdValue) {
                $id = $this->propertyReaderAccessor->hasPropertyValue(PropertyPath::createWith($aggregateIdName), $aggregate)
                    ? $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($aggregateIdName), $aggregate)
                    : null;

                if (!$id) {
                    throw NoCorrectIdentifierDefinedException::create("After calling {$this->aggregateInterface} has no identifier assigned. Please set up #[EventSourcingHandler] that will assign the id after first event");
                }

                $aggregateIds[$aggregateIdName] = $id;
            }
        }

        if ($this->isFactoryMethod()) {
            $message =
                MessageBuilder::fromMessage($message)
                    ->setPayload($aggregateIds)
                    ->build();
        }

        unset($metadata[MessageHeaders::REPLY_CHANNEL], );
        unset($metadata[AggregateMessage::AGGREGATE_ID]);
        unset($metadata[AggregateMessage::AGGREGATE_OBJECT]);
        unset($metadata[AggregateMessage::TARGET_VERSION]);
        unset($metadata[BusModule::COMMAND_CHANNEL_NAME_BY_NAME]);
        unset($metadata[BusModule::COMMAND_CHANNEL_NAME_BY_OBJECT]);
        unset($metadata[BusModule::EVENT_CHANNEL_NAME_BY_NAME]);
        unset($metadata[BusModule::EVENT_CHANNEL_NAME_BY_OBJECT]);
        unset($metadata[BusModule::QUERY_CHANNEL_NAME_BY_NAME]);
        unset($metadata[BusModule::QUERY_CHANNEL_NAME_BY_OBJECT]);
        unset($metadata[MessageHeaders::REPLY_CHANNEL]);
        unset($metadata[MessageHeaders::CONTENT_TYPE]);

        if ($this->isEventSourced) {
            $this->aggregateRepository->save($aggregateIds, is_string($this->aggregateInterface) ? $this->aggregateInterface : $this->aggregateInterface->getInterfaceName(), $events, $metadata, $versionBeforeHandling);
        }else {
            $this->aggregateRepository->save($aggregateIds, $aggregate, $metadata, $versionBeforeHandling);
        }

        foreach ($events as $event) {
            $this->objectEventBus->execute([$event, $metadata]);

            $eventDefinition = ClassDefinition::createFor(TypeDescriptor::createFromVariable($event));
            $namedEvent = TypeDescriptor::create(NamedEvent::class);
            if ($eventDefinition->hasClassAnnotation($namedEvent)) {
                /** @var NamedEvent $namedEvent */
                $namedEvent = $eventDefinition->getSingleClassAnnotation($namedEvent);

                $this->namedEventBus->execute([$namedEvent->getName(), $event, MediaType::APPLICATION_X_PHP, $metadata]);
            }
        }

        return MessageBuilder::fromMessage($message)
                ->build();
    }

    private function isFactoryMethod(): bool
    {
        return $this->isFactoryMethod;
    }
}