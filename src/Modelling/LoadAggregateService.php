<?php declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Modelling\Event;
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
    private EventSourcingHandlerExecutor $eventSourcingHandlerExecutor;
    private LoadAggregateMode $loadAggregateMode;
    private bool $isEventSourced;
    private bool $isAggregateVersionAutomaticallyIncreased;
    private PropertyEditorAccessor $propertyEditorAccessor;
    private ?string $aggregateVersionPropertyName;

    public function __construct(StandardRepository|EventSourcedRepository $aggregateRepository, string $aggregateClassName, bool $isEventSourced, string $aggregateMethod, ?string $messageVersionPropertyName, ?string $aggregateVersionPropertyName, bool $isAggregateVersionAutomaticallyIncreased, PropertyReaderAccessor $propertyReaderAccessor, PropertyEditorAccessor $propertyEditorAccessor, EventSourcingHandlerExecutor $eventSourcingHandlerExecutor, LoadAggregateMode $loadAggregateMode)
    {
        $this->aggregateRepository          = $aggregateRepository;
        $this->aggregateClassName = $aggregateClassName;
        $this->aggregateMethod = $aggregateMethod;
        $this->propertyReaderAccessor = $propertyReaderAccessor;
        $this->messageMessageVersionPropertyName = $messageVersionPropertyName;
        $this->eventSourcingHandlerExecutor = $eventSourcingHandlerExecutor;
        $this->loadAggregateMode = $loadAggregateMode;
        $this->isEventSourced = $isEventSourced;
        $this->isAggregateVersionAutomaticallyIncreased = $isAggregateVersionAutomaticallyIncreased;
        $this->propertyEditorAccessor = $propertyEditorAccessor;
        $this->aggregateVersionPropertyName = $aggregateVersionPropertyName;
    }

    public function load(Message $message) : ?Message
    {
        $aggregateIdentifiers = $message->getHeaders()->get(AggregateMessage::AGGREGATE_ID);

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

        $aggregateOrEventStream = $this->aggregateRepository->findBy($this->aggregateClassName, $aggregateIdentifiers);
        $aggregateVersion = null;
        if ($this->isEventSourced) {
            if (!$aggregateOrEventStream->getEvents()) {
                $aggregateOrEventStream = null;
            }else {
                $aggregateVersion = $aggregateOrEventStream->getAggregateVersion();
                $aggregateOrEventStream = $this->eventSourcingHandlerExecutor->fill($aggregateOrEventStream->getEvents(), null);
            }
        }

        if (!$aggregateOrEventStream && $this->loadAggregateMode->isDroppingMessageOnNotFound()) {
            return null;
        }

        if (!$aggregateOrEventStream && $this->loadAggregateMode->isThrowingOnNotFound()) {
            throw AggregateNotFoundException::create("Aggregate {$this->aggregateClassName} for calling {$this->aggregateMethod} was not found using identifiers " . \json_encode($aggregateIdentifiers));
        }

        $messageBuilder = MessageBuilder::fromMessage($message);
        if ($aggregateOrEventStream) {
            if (!is_null($aggregateVersion) && $this->isAggregateVersionAutomaticallyIncreased) {
                $this->propertyEditorAccessor->enrichDataWith(PropertyPath::createWith($this->aggregateVersionPropertyName), $aggregateOrEventStream, $aggregateVersion, $message, null);
            }
            $messageBuilder = $messageBuilder->setHeader(AggregateMessage::AGGREGATE_OBJECT, $aggregateOrEventStream);
        }
        if (!is_null($this->messageMessageVersionPropertyName)) {
            $messageBuilder = $messageBuilder->setHeader(AggregateMessage::TARGET_VERSION, $expectedVersion);
        }

        if (!$message->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL)) {
            $messageBuilder = $messageBuilder
                                ->setReplyChannel(NullableMessageChannel::create());
        }

        return $messageBuilder
            ->setHeader(AggregateMessage::AGGREGATE_OBJECT_EXISTS, !is_null($aggregateOrEventStream))
            ->build();
    }
}