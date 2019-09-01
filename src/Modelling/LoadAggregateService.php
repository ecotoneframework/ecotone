<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\NullableMessageChannel;
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
     * @var AggregateRepository
     */
    private $aggregateRepository;
    /**
     * @var bool
     */
    private $isFactoryMethod;
    /**
     * @var string
     */
    private $aggregateClassName;
    /**
     * @var string
     */
    private $aggregateMethod;
    /**
     * @var array
     */
    private $aggregateIdentifierMapping;
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
     * @var bool
     */
    private $loadForFactoryMethod;

    /**
     * ServiceCallToAggregateAdapter constructor.
     *
     * @param AggregateRepository $aggregateRepository
     * @param string $aggregateClassName
     * @param string $aggregateMethod
     * @param bool $isFactoryMethod
     * @param array $aggregateIdentifierMapping
     * @param null|array $expectedVersionMapping
     * @param PropertyReaderAccessor $propertyReaderAccessor
     * @param bool $dropMessageOnNotFound
     * @param bool $loadForFactoryMethod
     */
    public function __construct(AggregateRepository $aggregateRepository, string $aggregateClassName, string $aggregateMethod, bool $isFactoryMethod, array $aggregateIdentifierMapping, ?array $expectedVersionMapping, PropertyReaderAccessor $propertyReaderAccessor, bool $dropMessageOnNotFound, bool $loadForFactoryMethod)
    {
        $this->aggregateRepository          = $aggregateRepository;
        $this->isFactoryMethod = $isFactoryMethod;
        $this->aggregateClassName = $aggregateClassName;
        $this->aggregateMethod = $aggregateMethod;
        $this->aggregateIdentifierMapping = $aggregateIdentifierMapping;
        $this->propertyReaderAccessor = $propertyReaderAccessor;
        $this->expectedVersionMapping = $expectedVersionMapping;
        $this->dropMessageOnNotFound = $dropMessageOnNotFound;
        $this->loadForFactoryMethod = $loadForFactoryMethod;
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
        $aggregateIdentifiers = [];
        $expectedVersion = null;

        foreach ($this->aggregateIdentifierMapping as $aggregateIdentifierName => $aggregateIdentifierMappingName) {
            $aggregateIdentifiers[$aggregateIdentifierName] = ($this->isFactoryMethod && !$this->loadForFactoryMethod)
                ? null
                : (
                    $this->propertyReaderAccessor->hasPropertyValue(PropertyPath::createWith($aggregateIdentifierMappingName), $message->getPayload())
                    ? $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($aggregateIdentifierMappingName), $message->getPayload())
                    : null
                );
        }

        $aggregate = null;
        if (!$this->isFactoryMethod || $this->loadForFactoryMethod) {

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

            if (!$aggregate && $this->dropMessageOnNotFound) {
                return null;
            }

            if (!$aggregate && !$this->loadForFactoryMethod) {
                throw AggregateNotFoundException::create("Aggregate {$this->aggregateClassName} was not found for indentifiers " . \json_encode($aggregateIdentifiers));
            }
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
            ->setHeader(AggregateMessage::CLASS_NAME, $this->aggregateClassName)
            ->setHeader(AggregateMessage::METHOD_NAME, $this->aggregateMethod)
            ->setHeader(AggregateMessage::AGGREGATE_ID, $aggregateIdentifiers)
            ->setHeader(AggregateMessage::IS_FACTORY_METHOD, $this->isFactoryMethod)
            ->setHeader(AggregateMessage::CALLING_MESSAGE, $message)
            ->build();
    }
}