<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class LoadAggregateService
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class LoadAggregateService
{
    /**
     * @var StandardRepository|EventSourcedRepository
     */
    private $aggregateRepository;
    /**
     * @var string
     */
    private $aggregateClassName;
    /**
     * @var string
     */
    private $aggregateMethod;
    /**
     * @var PropertyReaderAccessor
     */
    private $propertyReaderAccessor;
    /**
     * @var null|array
     */
    private $expectedVersionMapping;
    /**
     * @var bool
     */
    private $dropMessageOnNotFound;
    /**
     * @var string|null
     */
    private $eventSourcedFactoryMethod;

    public function __construct(object $aggregateRepository, string $aggregateClassName, string $aggregateMethod, ?array $expectedVersionMapping, PropertyReaderAccessor $propertyReaderAccessor, bool $dropMessageOnNotFound)
    {
        $this->aggregateRepository          = $aggregateRepository;
        $this->aggregateClassName = $aggregateClassName;
        $this->aggregateMethod = $aggregateMethod;
        $this->propertyReaderAccessor = $propertyReaderAccessor;
        $this->expectedVersionMapping = $expectedVersionMapping;
        $this->dropMessageOnNotFound = $dropMessageOnNotFound;
        $this->eventSourcedFactoryMethod = $aggregateClassName instanceof EventSourcedRepository;
    }

    /**
     * @param Message $message
     *
     * @return Message
     * @throws AggregateNotFoundException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function load(Message $message) : ?Message
    {
        $aggregateIdentifiers = $message->getHeaders()->get(AggregateMessage::AGGREGATE_ID);
        $expectedVersion = null;

        $aggregate = null;
        foreach ($aggregateIdentifiers as $identifierName => $aggregateIdentifier) {
            if (is_null($aggregateIdentifier)) {
                $messageType = TypeDescriptor::createFromVariable($message->getPayload());
                throw AggregateNotFoundException::create("Aggregate identifier {$identifierName} definition found in {$messageType->toString()}, but is null. Can't load aggregate {$this->aggregateClassName} to call {$this->aggregateMethod}.");
            }
        }

        $expectedVersion = $this->expectedVersionMapping
            ? (
                $this->propertyReaderAccessor->hasPropertyValue(PropertyPath::createWith(array_key_first($this->expectedVersionMapping)), $message->getPayload())
                ? $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith(array_key_first($this->expectedVersionMapping)), $message->getPayload())
                : null
            )
            : null;

        $aggregate = $this->aggregateRepository->findBy($this->aggregateClassName, $aggregateIdentifiers);
        if ($this->aggregateRepository instanceof EventSourcedRepository) {
            Assert::isIterable($aggregate, "Event Sourced Repository must return iterable events for findBy method");
            $aggregate = call_user_func([$this->aggregateClassName, $this->eventSourcedFactoryMethod], $aggregate);
        }

        if (!$aggregate && $this->dropMessageOnNotFound) {
            return null;
        }

        if (!$aggregate) {
            throw AggregateNotFoundException::create("Aggregate {$this->aggregateClassName}:{$this->aggregateMethod} was not found for indentifiers " . \json_encode($aggregateIdentifiers));
        }

        $messageBuilder = MessageBuilder::fromMessage($message);
        if ($aggregate) {
            $messageBuilder = $messageBuilder->setHeader(AggregateMessage::AGGREGATE_OBJECT, $aggregate);
        }
        if (!is_null($this->expectedVersionMapping)) {
            $messageBuilder = $messageBuilder->setHeader(AggregateMessage::TARGET_VERSION, $expectedVersion);
        }

        if (!$message->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL)) {
            $messageBuilder = $messageBuilder
                                ->setReplyChannel(NullableMessageChannel::create());
        }

        return $messageBuilder
            ->build();
    }
}