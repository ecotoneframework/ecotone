<?php declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\EventSourcing\Event;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use PHPStan\Rules\Properties\PropertyDescriptor;

/**
 * Class LoadAggregateService
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class LoadAggregateService
{
    private StandardRepository|EventSourcedRepository $aggregateRepository;
    private string $aggregateClassName;
    private string $aggregateMethod;
    private PropertyReaderAccessor $propertyReaderAccessor;
    private ?string $messageMessageVersionPropertyName;
    private ?string $eventSourcedFactoryMethod;
    private LoadAggregateMode $loadAggregateMode;
    private bool $isEventSourced;
    private bool $isAggregateVersionAutomaticallyIncreased;
    private PropertyEditorAccessor $propertyEditorAccessor;
    private ?string $aggregateVersionPropertyName;

    public function __construct(StandardRepository|EventSourcedRepository $aggregateRepository, string $aggregateClassName, bool $isEventSourced, string $aggregateMethod, ?string $messageVersionPropertyName, ?string $aggregateVersionPropertyName, bool $isAggregateVersionAutomaticallyIncreased, PropertyReaderAccessor $propertyReaderAccessor, PropertyEditorAccessor $propertyEditorAccessor, ?string $eventSourcedFactoryMethod, LoadAggregateMode $loadAggregateMode)
    {
        $this->aggregateRepository          = $aggregateRepository;
        $this->aggregateClassName = $aggregateClassName;
        $this->aggregateMethod = $aggregateMethod;
        $this->propertyReaderAccessor = $propertyReaderAccessor;
        $this->messageMessageVersionPropertyName = $messageVersionPropertyName;
        $this->eventSourcedFactoryMethod = $eventSourcedFactoryMethod;
        $this->loadAggregateMode = $loadAggregateMode;
        $this->isEventSourced = $isEventSourced;
        $this->isAggregateVersionAutomaticallyIncreased = $isAggregateVersionAutomaticallyIncreased;
        $this->propertyEditorAccessor = $propertyEditorAccessor;
        $this->aggregateVersionPropertyName = $aggregateVersionPropertyName;
    }

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

        $expectedVersion = $this->messageMessageVersionPropertyName
            ? (
                $this->propertyReaderAccessor->hasPropertyValue(PropertyPath::createWith($this->messageMessageVersionPropertyName), $message->getPayload())
                ? $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($this->messageMessageVersionPropertyName), $message->getPayload())
                : null
            )
            : null;

        $aggregate = $this->aggregateRepository->findBy($this->aggregateClassName, $aggregateIdentifiers);
        $aggregateVersion = null;
        if ($this->isEventSourced) {
            if (!$aggregate->getEvents()) {
                $aggregate = null;
            }else {
                $aggregateVersion = $aggregate->getAggregateVersion();
                $aggregate = call_user_func([$this->aggregateClassName, $this->eventSourcedFactoryMethod], array_map(fn(Event $event) : object => $event->getEvent(), $aggregate->getEvents()));
            }
        }

        if (!$aggregate && $this->loadAggregateMode->isDroppingMessageOnNotFound()) {
            return null;
        }

        if (!$aggregate && $this->loadAggregateMode->isThrowingOnNotFound()) {
            throw AggregateNotFoundException::create("Aggregate {$this->aggregateClassName} for calling {$this->aggregateMethod} was not found using identifiers " . \json_encode($aggregateIdentifiers));
        }

        $messageBuilder = MessageBuilder::fromMessage($message);
        if ($aggregate) {
            if (!is_null($aggregateVersion) && $this->isAggregateVersionAutomaticallyIncreased) {
                $this->propertyEditorAccessor->enrichDataWith(PropertyPath::createWith($this->aggregateVersionPropertyName), $aggregate, $aggregateVersion, $message, null);
            }
            $messageBuilder = $messageBuilder->setHeader(AggregateMessage::AGGREGATE_OBJECT, $aggregate);
        }
        if (!is_null($this->messageMessageVersionPropertyName)) {
            $messageBuilder = $messageBuilder->setHeader(AggregateMessage::TARGET_VERSION, $expectedVersion);
        }

        if (!$message->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL)) {
            $messageBuilder = $messageBuilder
                                ->setReplyChannel(NullableMessageChannel::create());
        }

        return $messageBuilder
            ->setHeader(AggregateMessage::AGGREGATE_OBJECT_EXISTS, !is_null($aggregate))
            ->build();
    }
}