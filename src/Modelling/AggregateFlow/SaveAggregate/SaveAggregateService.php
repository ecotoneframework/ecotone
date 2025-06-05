<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateResolver;
use Ecotone\Modelling\Event;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\Repository\AggregateRepository;

/**
 * licence Apache-2.0
 */
final class SaveAggregateService implements MessageProcessor
{
    public const NO_SNAPSHOT_THRESHOLD = 0;
    public const SNAPSHOT_COLLECTION = 'aggregate_snapshots_';

    public function __construct(
        private AggregateRepository $aggregateRepository,
        private PropertyReaderAccessor $propertyReaderAccessor,
        private AggregateResolver $aggregateResolver,
        private EventBus $eventBus,
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
            $versionAfterHandling = $this->aggregateRepository->save($resolvedAggregate, $metadata);

            /** For ORM identifier may be assigned after saving */
            $resolvedAggregates[$key] = $resolvedAggregate->withIdentifiers(
                SaveAggregateServiceTemplate::getAggregateIds(
                    $this->propertyReaderAccessor,
                    $message->getHeaders()->headers(),
                    $resolvedAggregate->getAggregateInstance(),
                    $resolvedAggregate->getAggregateClassDefinition(),
                    true,
                )
            )->withVersionAfterHandling($versionAfterHandling);
        }

        foreach ($resolvedAggregates as $resolvedAggregate) {
            $this->publishEvents($resolvedAggregate->getEvents());
        }

        return SaveAggregateServiceTemplate::buildReplyMessage(
            $resolvedAggregates[0]->isNewInstance(),
            $resolvedAggregates[0]->isNewInstance() ? $resolvedAggregates[0]->getIdentifiers() : [],
            $resolvedAggregates[0]->getVersionAfterHandling(),
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
        }
    }

    public static function getSnapshotCollectionName(string $aggregateClassname): string
    {
        return self::SNAPSHOT_COLLECTION . $aggregateClassname;
    }

    public static function getSnapshotDocumentId(array $identifiers): string
    {
        return count($identifiers) === 1 ? (string)reset($identifiers) : json_encode($identifiers);
    }
}
