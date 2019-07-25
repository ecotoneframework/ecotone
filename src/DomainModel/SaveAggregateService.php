<?php

namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\DomainModel\Config\AggregateMessagingModule;
use SimplyCodedSoftware\DomainModel\LazyEventBus\LazyEventBus;
use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyEditorAccessor;
use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyReaderAccessor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class SaveAggregateService
 * @package SimplyCodedSoftware\DomainModel
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
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