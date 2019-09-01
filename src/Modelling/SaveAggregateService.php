<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\LazyEventBus\LazyEventBus;

/**
 * Class SaveAggregateService
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SaveAggregateService
{
    /**
     * @var AggregateRepository
     */
    private $aggregateRepository;
    /**
     * @var PropertyReaderAccessor
     */
    private $propertyReaderAccessor;
    /**
     * @var LazyEventBus
     */
    private $lazyEventBus;
    /**
     * @var string|null
     */
    private $eventMethod;
    /**
     * @var PropertyEditorAccessor
     */
    private $propertyEditorAccessor;
    /**
     * @var array|null
     */
    private $versionMapping;

    /**
     * SaveAggregateService constructor.
     * @param AggregateRepository $aggregateRepository
     * @param PropertyEditorAccessor $propertyEditorAccessor
     * @param PropertyReaderAccessor $propertyReaderAccessor
     * @param LazyEventBus $lazyEventBus
     * @param string|null $eventMethod
     * @param array|null $versionMapping
     */
    public function __construct(AggregateRepository $aggregateRepository, PropertyEditorAccessor $propertyEditorAccessor, PropertyReaderAccessor $propertyReaderAccessor, LazyEventBus $lazyEventBus, ?string $eventMethod, ?array $versionMapping)
    {
        $this->aggregateRepository = $aggregateRepository;
        $this->propertyReaderAccessor = $propertyReaderAccessor;
        $this->lazyEventBus = $lazyEventBus;
        $this->eventMethod = $eventMethod;
        $this->propertyEditorAccessor = $propertyEditorAccessor;
        $this->versionMapping = $versionMapping;
    }

    /**
     * @param Message $message
     *
     * @return Message
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function save(Message $message) : ?Message
    {
        $aggregate = $message->getHeaders()->get(AggregateMessage::AGGREGATE_OBJECT);

        $nextVersion = null;
        if ($this->versionMapping) {
            $aggregatePropertyName = reset($this->versionMapping);
            $nextVersion = $this->propertyReaderAccessor->getPropertyValue(
                PropertyPath::createWith($aggregatePropertyName),
                $aggregate
            );
            $nextVersion = is_null($nextVersion) ? 1 : $nextVersion + 1;

            $this->propertyEditorAccessor->enrichDataWith(
                PropertyPath::createWith($aggregatePropertyName),
                $aggregate,
                $nextVersion,
                $message,
                null
            );
        }

        $aggregateIds = [];
        foreach ($message->getHeaders()->get(AggregateMessage::AGGREGATE_ID) as $aggregateIdName => $aggregateIdValue) {
            $aggregateIds[$aggregateIdName] = $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($aggregateIdName), $aggregate);
        }

        if ($message->getHeaders()->get(AggregateMessage::IS_FACTORY_METHOD)) {
            $message =
                MessageBuilder::fromMessage($message)
                    ->setPayload($aggregateIds)
                    ->build();
        }
        $version = null;
        if ($nextVersion && $message->getHeaders()->containsKey(AggregateMessage::TARGET_VERSION)) {
            $expectedVersion = $message->getHeaders()->get(AggregateMessage::TARGET_VERSION);
            if ($expectedVersion && $nextVersion != $expectedVersion + 1) {
                throw AggregateVersionMismatchException::create("Aggregate version is different");
            }
        }

        /** @var Message $callingMessage */
        $callingMessage = $message->getHeaders()->get(AggregateMessage::CALLING_MESSAGE);
        $metadata = $callingMessage->getHeaders()->headers();
        unset($metadata[MessageHeaders::REPLY_CHANNEL]);

        $this->aggregateRepository->save($aggregateIds, $aggregate, $metadata, $nextVersion);

        if ($this->eventMethod) {
            $events = call_user_func([$aggregate, $this->eventMethod]);
            foreach ($events as $event) {
                $this->lazyEventBus->sendWithMetadata($event, $metadata);
            }
        }

        return $message;
    }
}