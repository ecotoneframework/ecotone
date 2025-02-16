<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\LoadAggregate;

use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Store\Document\DocumentException;
use Ecotone\Messaging\Store\Document\DocumentStore;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\SaveAggregateService;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\AggregateNotFoundException;
use Ecotone\Modelling\Attribute\AggregateVersion;
use Ecotone\Modelling\BaseEventSourcingConfiguration;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\EventSourcingExecutor\EventSourcingHandlerExecutor;
use Psr\Container\ContainerInterface;

/**
 * licence Apache-2.0
 */
final class LoadEventSourcingAggregateService implements MessageProcessor
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
        private LoadAggregateMode $loadAggregateMode,
        private ContainerInterface $container,
        private BaseEventSourcingConfiguration $eventSourcingConfiguration,
        private LoggingGateway $loggingGateway,
    ) {
    }

    public function process(Message $message): ?Message
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

        $aggregateVersion = 1;
        $aggregate = null;
        foreach ($this->eventSourcingConfiguration->getSnapshotsConfig() as $aggregateClass => $config) {
            if ($aggregateClass === $this->aggregateClassName) {
                /** @var DocumentStore $documentStore */
                $documentStore = $this->container->get($config['documentStore']);

                try {
                    $aggregate = $documentStore->findDocument(SaveAggregateService::getSnapshotCollectionName($this->aggregateClassName), SaveAggregateService::getSnapshotDocumentId($aggregateIdentifiers));
                } catch (DocumentException $documentException) {
                    $this->loggingGateway->error("Failure during loading snapshot for aggregate {$this->aggregateClassName} with identifiers " . json_encode($aggregateIdentifiers) . '. Snapshot ignored to self system system. Error: ' . $documentException->getMessage(), [
                        'exception' => $documentException,
                    ]);
                }

                if ($aggregate !== null && $aggregate::class === $this->aggregateClassName) {
                    $aggregateVersion = $this->getAggregateVersion($aggregate);
                    Assert::isTrue($aggregateVersion > 0, sprintf('Serialization for snapshot of %s is set incorrectly, it does not serialize aggregate version', $aggregate::class));
                } elseif ($aggregate !== null) {
                    $this->loggingGateway->error("Snapshot for aggregate {$this->aggregateClassName} was found, but it is not instance of {$this->aggregateClassName}. It is type of " . gettype($aggregate) . '. Snapshot ignored to self-heal system.');
                    $aggregate = null;
                }
            }
        }

        $eventStream = $this->repository->findBy($this->aggregateClassName, $aggregateIdentifiers, $aggregate === null ? 1 : ($aggregateVersion + 1));

        if (! $eventStream->getEvents()) {
            $eventStream = $aggregate;
        } else {
            $aggregateVersion = $eventStream->getAggregateVersion();
            $eventStream = $this->eventSourcingHandlerExecutor->fill($eventStream->getEvents(), $aggregate);
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
            $resultMessage = $resultMessage->setHeader(AggregateMessage::CALLED_AGGREGATE_INSTANCE, $eventStream);
        }

        if (! $message->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL)) {
            $resultMessage = $resultMessage->setReplyChannel(NullableMessageChannel::create());
        }

        return $resultMessage->build();
    }

    private function getAggregateVersion(object|array|string $aggregate): mixed
    {
        $propertyReader = new PropertyReaderAccessor();
        $versionAnnotation = TypeDescriptor::create(AggregateVersion::class);
        $aggregateVersionPropertyName = null;
        foreach (ClassDefinition::createFor(TypeDescriptor::createFromVariable($aggregate))->getProperties() as $property) {
            if ($property->hasAnnotation($versionAnnotation)) {
                $aggregateVersionPropertyName = $property->getName();
                break;
            }
        }

        return $propertyReader->getPropertyValue(
            PropertyPath::createWith($aggregateVersionPropertyName),
            $aggregate
        );
    }
}
