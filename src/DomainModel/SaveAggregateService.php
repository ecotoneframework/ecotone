<?php

namespace Ecotone\DomainModel;

use Ecotone\DomainModel\Config\AggregateMessagingModule;
use Ecotone\DomainModel\LazyEventBus\LazyEventBus;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class SaveAggregateService
 * @package Ecotone\DomainModel
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
     * SaveAggregateService constructor.
     * @param AggregateRepository $aggregateRepository
     * @param PropertyReaderAccessor $propertyReaderAccessor
     * @param LazyEventBus $lazyEventBus
     * @param string|null $eventMethod
     */
    public function __construct(AggregateRepository $aggregateRepository, PropertyReaderAccessor $propertyReaderAccessor, LazyEventBus $lazyEventBus, ?string $eventMethod)
    {
        $this->aggregateRepository = $aggregateRepository;
        $this->propertyReaderAccessor = $propertyReaderAccessor;
        $this->lazyEventBus = $lazyEventBus;
        $this->eventMethod = $eventMethod;
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

        $aggregateIds = [];
        if ($message->getHeaders()->get(AggregateMessage::IS_FACTORY_METHOD)) {
            $aggregate = $message->getHeaders()->get(AggregateMessage::AGGREGATE_OBJECT);
            $aggregateIds = [];

            foreach ($message->getHeaders()->get(AggregateMessage::AGGREGATE_ID) as $aggregateIdName => $aggregateIdValue) {
                $aggregateIds[$aggregateIdName] = $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($aggregateIdName), $aggregate);
            }

            $message =
                MessageBuilder::fromMessage($message)
                    ->setPayload($aggregateIds)
                    ->build();
        }

        /** @var Message $callingMessage */
        $callingMessage = $message->getHeaders()->get(AggregateMessage::CALLING_MESSAGE);
        $metadata = $callingMessage->getHeaders()->headers();
        $this->aggregateRepository->save($aggregateIds, $aggregate, $metadata);

        if ($this->eventMethod) {
            $events = call_user_func([$aggregate, $this->eventMethod]);
            foreach ($events as $event) {
                $this->lazyEventBus->sendWithMetadata($event, $metadata);
            }
        }

        return $message;
    }
}