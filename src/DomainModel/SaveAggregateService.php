<?php

namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\DomainModel\Config\AggregateMessagingModule;
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
     * SaveAggregateService constructor.
     * @param AggregateRepository $aggregateRepository
     * @param PropertyReaderAccessor $propertyReaderAccessor
     */
    public function __construct(AggregateRepository $aggregateRepository, PropertyReaderAccessor $propertyReaderAccessor)
    {
        $this->aggregateRepository = $aggregateRepository;
        $this->propertyReaderAccessor = $propertyReaderAccessor;
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

        $this->aggregateRepository->save($aggregate);

        if ($message->getHeaders()->get(AggregateMessage::IS_FACTORY_METHOD)) {
            $aggregate = $message->getHeaders()->get(AggregateMessage::AGGREGATE_OBJECT);
            $aggregateIds = [];

            foreach ($message->getHeaders()->get(AggregateMessage::AGGREGATE_ID) as $aggregateIdName => $aggregateIdValue) {
                $aggregateIds[$aggregateIdName] = $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($aggregateIdName), $aggregate);
            }

            $payload = count($aggregateIds) === 1 ? array_shift($aggregateIds) : $aggregateIds;

            return
                MessageBuilder::fromMessage($message)
                    ->setPayload($payload)
                    ->build();
        }

        return $message;
    }
}