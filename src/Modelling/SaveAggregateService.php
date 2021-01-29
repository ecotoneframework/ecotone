<?php

namespace Ecotone\Modelling;

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
use Ecotone\Modelling\Config\BusModule;

/**
 * Class SaveAggregateService
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SaveAggregateService
{
    /**
     * @var StandardRepository|EventSourcedRepository
     */
    private object $aggregateRepository;
    private PropertyReaderAccessor $propertyReaderAccessor;
    /**
     * @var NonProxyGateway|EventBus
     */
    private object $eventBus;
    private ?string $eventRetrievingMethod;
    private PropertyEditorAccessor $propertyEditorAccessor;
    private array $aggregateIdentifierMapping;
    private InterfaceToCall $aggregateInterface;
    private ?array $aggregateVersionMapping;
    private bool $isAggregateVersionAutomaticallyIncreased;

    public function __construct(InterfaceToCall $aggregateInterface, object $aggregateRepository, PropertyEditorAccessor $propertyEditorAccessor, PropertyReaderAccessor $propertyReaderAccessor, NonProxyGateway $eventBus, ?string $eventMethod, array $aggregateIdentifierMapping, ?array $aggregateVersionMapping, bool $isAggregateVersionAutomaticallyIncreased)
    {
        $this->aggregateRepository = $aggregateRepository;
        $this->propertyReaderAccessor = $propertyReaderAccessor;
        $this->eventBus = $eventBus;
        $this->propertyEditorAccessor = $propertyEditorAccessor;
        $this->eventRetrievingMethod = $eventMethod;
        $this->aggregateIdentifierMapping = $aggregateIdentifierMapping;
        $this->aggregateInterface = $aggregateInterface;
        $this->aggregateVersionMapping = $aggregateVersionMapping;
        $this->isAggregateVersionAutomaticallyIncreased = $isAggregateVersionAutomaticallyIncreased;
    }

    public function save(Message $message, array $metadata) : \Ecotone\Messaging\Message
    {
        $aggregate = $message->getHeaders()->get(AggregateMessage::AGGREGATE_OBJECT);
        $isEventSourced = $this->aggregateRepository instanceof EventSourcedRepository;
        $events = [];

        if ($isEventSourced) {
            $events = $message->getPayload();
            Assert::isIterable($events, "Return value Event Sourced Aggregate {$this->aggregateInterface} must return iterable events");
        }elseif ($this->eventRetrievingMethod) {
            $events = call_user_func([$aggregate, $this->eventRetrievingMethod]);
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

        if ($this->aggregateVersionMapping && $this->isAggregateVersionAutomaticallyIncreased && !$isEventSourced) {
            $aggregatePropertyName = reset($this->aggregateVersionMapping);
// @TODO prepare test for calling when no automatic increased is enabled

            $this->propertyEditorAccessor->enrichDataWith(
                PropertyPath::createWith($aggregatePropertyName),
                $aggregate,
                $versionBeforeHandling + 1,
                $message,
                null
            );
        }

        $aggregateIds = [];
        foreach ($this->aggregateIdentifierMapping as $aggregateIdName => $aggregateIdValue) {
            $id = $this->propertyReaderAccessor->hasPropertyValue(PropertyPath::createWith($aggregateIdName), $aggregate)
                    ? $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($aggregateIdName), $aggregate)
                    : null;

            if (!$id) {
                throw NoCorrectIdentifierDefinedException::create("After calling {$this->aggregateInterface} has no identifier assigned. Please provide implementation for #[AggregateFactory], which assigns identifier to the aggregate.");
            }

            $aggregateIds[$aggregateIdName] = $id;
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

        if ($isEventSourced) {
            $this->aggregateRepository->save($aggregateIds, $this->aggregateInterface->getInterfaceName(), $events, $metadata, $versionBeforeHandling);
        }else {
            $this->aggregateRepository->save($aggregateIds, $aggregate, $metadata, $versionBeforeHandling);
        }

        foreach ($events as $event) {
            $this->eventBus->execute([$event, $metadata]);
        }

        return MessageBuilder::fromMessage($message)
                ->build();
    }

    private function isFactoryMethod(): bool
    {
        return $this->aggregateInterface->isStaticallyCalled();
    }
}