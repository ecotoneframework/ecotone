<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateResolver;
use Ecotone\Modelling\Attribute\NamedEvent;
use Ecotone\Modelling\BaseEventSourcingConfiguration;
use Ecotone\Modelling\Event;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\StandardRepository;
use Psr\Container\ContainerInterface;

/**
 * licence Apache-2.0
 */
final class SaveAggregateService implements MessageProcessor
{
    public const NO_SNAPSHOT_THRESHOLD = 0;
    public const SNAPSHOT_COLLECTION = 'aggregate_snapshots_';

    public function __construct(
        private EventSourcedRepository $eventSourcedAggregateRepository,
        private PropertyReaderAccessor $propertyReaderAccessor,
        private StandardRepository     $standardRepository,
        private AggregateResolver      $aggregateResolver,
        private BaseEventSourcingConfiguration $eventSourcingConfiguration,
        private bool $publishEvents,
        private EventBus $eventBus,
        private ContainerInterface $container,
    ) {

    }

    public function process(Message $message): Message|null
    {
        $resolvedAggregates = $this->aggregateResolver->resolve($message);
        $metadata = MessageHeaders::unsetNonUserKeys($message->getHeaders()->headers());

        if (! $resolvedAggregates) {
            return MessageBuilder::fromMessage($message)->build();
        }

        foreach ($resolvedAggregates as $key => $resolvedAggregate) {
            $version = $resolvedAggregate->getVersionBeforeHandling();

            if (! $resolvedAggregate->getAggregateClassDefinition()->isEventSourced()) {
                $this->standardRepository->save(
                    $resolvedAggregate->getIdentifiers(),
                    $resolvedAggregate->getAggregateInstance(),
                    $metadata,
                    $version
                );

                /** For ORM identifier may be assigned after saving */
                $resolvedAggregates[$key] = $resolvedAggregate->withIdentifiers(
                    SaveAggregateServiceTemplate::getAggregateIds(
                        $this->propertyReaderAccessor,
                        $message->getHeaders()->headers(),
                        $resolvedAggregate->getAggregateInstance(),
                        $resolvedAggregate->getAggregateClassDefinition(),
                        true,
                    )
                );

                continue;
            }

            if ($this->eventSourcingConfiguration->useSnapshotFor($resolvedAggregate->getAggregateClassName())) {
                $snapshotTriggerThreshold = $this->eventSourcingConfiguration->getSnapshotTriggerThresholdFor($resolvedAggregate->getAggregateClassName());
                foreach ($resolvedAggregate->getEvents() as $event) {
                    $version += 1;
                    if ($version % $snapshotTriggerThreshold === 0) {
                        $identifiers = $resolvedAggregate->getIdentifiers();
                        Assert::isTrue(count($identifiers) === 1, 'Snapshoting is possible only for aggregates having single identifiers');

                        $documentStore = $this->container->get(
                            $this->eventSourcingConfiguration->getDocumentStoreReferenceFor($resolvedAggregate->getAggregateClassName())
                        );
                        $documentStore->upsertDocument(self::getSnapshotCollectionName($resolvedAggregate->getAggregateClassName()), reset($identifiers), $resolvedAggregate->getAggregateInstance());
                    }
                }
            }

            $this->eventSourcedAggregateRepository->save($resolvedAggregate->getIdentifiers(), $resolvedAggregate->getAggregateClassName(), $resolvedAggregate->getEvents(), $metadata, $resolvedAggregate->getVersionBeforeHandling());
        }

        if ($this->publishEvents) {
            foreach ($resolvedAggregates as $resolvedAggregate) {
                $this->publishEvents($resolvedAggregate->getEvents());
            }
        }

        return SaveAggregateServiceTemplate::buildReplyMessage(
            $resolvedAggregates[0]->isNewInstance(),
            $resolvedAggregates[0]->isNewInstance() ? $resolvedAggregates[0]->getIdentifiers() : [],
            $message,
        );
    }

    /**
     * @param Event[] $events
     */
    private function publishEvents(array $events): void
    {
        foreach ($events as $event) {
            $this->eventBus->publish($event->getPayload(), $event->getMetadata());

            $eventDefinition = ClassDefinition::createFor(TypeDescriptor::createFromVariable($event->getPayload()));
            $namedEvent = TypeDescriptor::create(NamedEvent::class);
            if ($eventDefinition->hasClassAnnotation($namedEvent)) {
                /** @var NamedEvent $namedEvent */
                $namedEvent = $eventDefinition->getSingleClassAnnotation($namedEvent);

                $this->eventBus->publishWithRouting($namedEvent->getName(), $event->getPayload(), MediaType::APPLICATION_X_PHP, $event->getMetadata());
            }
        }
    }

    public static function getSnapshotCollectionName(string $aggregateClassname): string
    {
        return self::SNAPSHOT_COLLECTION . $aggregateClassname;
    }
}
