<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\LoadAggregate;

use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\AggregateNotFoundException;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\EventSourcingExecutor\EventSourcingHandlerExecutor;
use Ecotone\Modelling\LoadAggregateService;

/**
 * licence Apache-2.0
 */
final class LoadEventSourcingAggregateService implements LoadAggregateService
{
    public function __construct(
        private EventSourcedRepository $repository,
        private string $aggregateClassName,
        private string $aggregateMethod,
        private ?string $messageVersionPropertyName,
        private ?string $aggregateVersionPropertyName,
        private bool $isAggregateVersionAutomaticallyIncreased,
        private PropertyReaderAccessor $propertyReaderAccessor,
        private PropertyEditorAccessor $propertyEditorAccessor,
        private EventSourcingHandlerExecutor $eventSourcingHandlerExecutor,
        private LoadAggregateMode $loadAggregateMode
    ) {
    }

    public function load(Message $message): ?Message
    {
        $resultMessage = MessageBuilder::fromMessage($message);

        $aggregateIdentifiers = $message->getHeaders()->get(AggregateMessage::AGGREGATE_ID);

        foreach ($aggregateIdentifiers as $identifierName => $aggregateIdentifier) {
            if (is_null($aggregateIdentifier)) {
                $messageType = TypeDescriptor::createFromVariable($message->getPayload());
                throw AggregateNotFoundException::create("Aggregate identifier {$identifierName} definition found in {$messageType->toString()}, but is null. Can't load aggregate {$this->aggregateClassName} to call {$this->aggregateMethod}.");
            }
        }

        if (! is_null($this->messageVersionPropertyName)) {
            $expectedVersion = null;
            if ($this->propertyReaderAccessor->hasPropertyValue(PropertyPath::createWith($this->messageVersionPropertyName), $message->getPayload())) {
                $expectedVersion = $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($this->messageVersionPropertyName), $message->getPayload());
            }

            $resultMessage = $resultMessage->setHeader(AggregateMessage::TARGET_VERSION, $expectedVersion);
        }

        $aggregateVersion = null;
        $eventStream = $this->repository->findBy($this->aggregateClassName, $aggregateIdentifiers);

        if (! $eventStream->getEvents()) {
            $eventStream = null;
        } else {
            $aggregateVersion = $eventStream->getAggregateVersion();
            $eventStream = $this->eventSourcingHandlerExecutor->fill($eventStream->getEvents(), null);
        }

        if (! $eventStream && $this->loadAggregateMode->isDroppingMessageOnNotFound()) {
            return null;
        }

        if (! $eventStream && $this->loadAggregateMode->isThrowingOnNotFound()) {
            if ($aggregateIdentifiers === []) {
                throw AggregateNotFoundException::create("Aggregate {$this->aggregateClassName} for calling {$this->aggregateMethod} was not as no identifiers were provided. Have you forgot to add use #[TargetIdentifier] in your Command or `aggregate.id` in metadata or provide #[Identifier] to MessageGateway?");
            }

            throw AggregateNotFoundException::create("Aggregate {$this->aggregateClassName} for calling {$this->aggregateMethod} was not found using identifiers " . json_encode($aggregateIdentifiers));
        }
        if ($eventStream) {
            if (! is_null($aggregateVersion) && $this->isAggregateVersionAutomaticallyIncreased) {
                $this->propertyEditorAccessor->enrichDataWith(PropertyPath::createWith($this->aggregateVersionPropertyName), $eventStream, $aggregateVersion, $message, null);
            }
            $resultMessage = $resultMessage->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $eventStream);
        }

        if (! $message->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL)) {
            $resultMessage = $resultMessage->setReplyChannel(NullableMessageChannel::create());
        }

        return $resultMessage
            ->setHeader(AggregateMessage::AGGREGATE_OBJECT_EXISTS, ! is_null($eventStream))
            ->build();
    }
}
