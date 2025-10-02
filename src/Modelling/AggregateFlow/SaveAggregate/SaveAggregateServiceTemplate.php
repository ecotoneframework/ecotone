<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\EventSourcing\Mapping\EventMapper;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Metadata\RevisionMetadataEnricher;
use Ecotone\Messaging\Scheduling\Clock;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\AggregateIdMetadata;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateClassDefinition;
use Ecotone\Modelling\AggregateIdResolver;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Event;
use Ecotone\Modelling\NoCorrectIdentifierDefinedException;
use Ramsey\Uuid\Uuid;

/**
 * licence Apache-2.0
 */
class SaveAggregateServiceTemplate
{
    public static function resolveVersionBeforeHandling(
        Message $message
    ): int {
        return $message->getHeaders()->containsKey(AggregateMessage::TARGET_VERSION) ? $message->getHeaders()->get(AggregateMessage::TARGET_VERSION) : 0;
    }

    public static function enrichVersionIfNeeded(
        PropertyEditorAccessor $propertyEditorAccessor,
        int    $versionBeforeHandling,
        object|string $aggregate,
        Message $message,
        ?string $aggregateVersionProperty,
        bool    $isAggregateVersionAutomaticallyIncreased,
    ) {
        if ($aggregateVersionProperty && $isAggregateVersionAutomaticallyIncreased) {
            $propertyEditorAccessor->enrichDataWith(
                PropertyPath::createWith($aggregateVersionProperty),
                $aggregate,
                $versionBeforeHandling + 1,
                $message,
                null
            );
        }
    }

    /**
     * @param array<string, string> $aggregateIdentifierMapping
     * @param array<string, string> $aggregateIds
     */
    public static function getAggregateIds(
        PropertyReaderAccessor $propertyReaderAccessor,
        array $metadata,
        object $aggregate,
        AggregateClassDefinition $aggregateDefinition,
        bool $throwOnNoIdentifier
    ): array {
        $aggregateIds = isset($metadata[AggregateMessage::AGGREGATE_ID]) ? AggregateIdMetadata::createFrom($metadata[AggregateMessage::AGGREGATE_ID])->getIdentifiers() : [];
        if ($aggregateIds && self::hasAnyIdentifiersDefined($aggregateIds)) {
            return $aggregateIds;
        }

        foreach ($aggregateDefinition->getAggregateIdentifierMapping() as $aggregateIdName => $aggregateIdValue) {
            if (isset($aggregateDefinition->getAggregateIdentifierGetMethods()[$aggregateIdName])) {
                $id = call_user_func([$aggregate, $aggregateDefinition->getAggregateIdentifierGetMethods()[$aggregateIdName]]);

                if (! is_null($id)) {
                    $aggregateIds[$aggregateIdName] = $id;
                }

                continue;
            }

            $id = $propertyReaderAccessor->hasPropertyValue(PropertyPath::createWith($aggregateIdName), $aggregate)
                ? $propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($aggregateIdName), $aggregate)
                : null;

            if (! $id) {
                if (! $throwOnNoIdentifier) {
                    continue;
                }

                throw NoCorrectIdentifierDefinedException::create("After calling {$aggregateDefinition->getClassName()} has no identifier assigned. If you're using Event Sourcing Aggregate, please set up #[EventSourcingHandler] that will assign the id after first event");
            }

            $aggregateIds[$aggregateIdName] = $id;
        }

        return AggregateIdResolver::resolveArrayOfIdentifiers(get_class($aggregate), $aggregateIds)->getIdentifiers();
    }

    /**
     * @param array<string, string> $aggregateIds
     */
    public static function buildReplyMessage(
        bool $isFactoryMethod,
        array $aggregateIds,
        int $versionAfterHandling,
        Message $message
    ): Message|null {
        if ($message->getHeaders()->containsKey(AggregateMessage::NULL_EXECUTION_RESULT)) {
            if ($message->getHeaders()->get(AggregateMessage::NULL_EXECUTION_RESULT)) {
                return null;
            }
        }

        $messageBuilder = MessageBuilder::fromMessage($message)
            ->setHeader(AggregateMessage::TARGET_VERSION, $versionAfterHandling)
        ;

        if ($isFactoryMethod) {
            if (count($aggregateIds) === 1) {
                $aggregateIds = reset($aggregateIds);
            }

            $messageBuilder = $messageBuilder->setPayload($aggregateIds);
        }

        return $messageBuilder->build();
    }

    /**
     * @return Event[]
     */
    public static function buildEcotoneEvents(mixed $events, string $calledInterface, Message $message, HeaderMapper $headerMapper, ConversionService $conversionService, EventMapper $eventMapper): array
    {
        Assert::isIterable($events, "Return value Event Sourced Aggregate {$calledInterface} must return array of events");
        return array_map(static function ($event) use ($message, $calledInterface, $headerMapper, $conversionService, $eventMapper): Event {
            if (! is_object($event)) {
                $typeDescriptor = Type::createFromVariable($event);
                throw InvalidArgumentException::create("Events return by after calling {$calledInterface} must all be objects, {$typeDescriptor->toString()} given");
            }
            $eventMetadata = $message->getHeaders()->headers();
            if ($event instanceof Event) {
                $eventMetadata = $event->getMetadata();
                $event = $event->getPayload();
            }
            $eventMetadata = MessageHeaders::unsetAllFrameworkHeaders($eventMetadata);
            /** This need to be removed explicitly after saving, to be passed correctly across asynchronous message channels */
            unset($eventMetadata[AggregateMessage::AGGREGATE_ID]);
            $eventMetadata = $headerMapper->mapFromMessageHeaders($eventMetadata, $conversionService);

            $eventMetadata = RevisionMetadataEnricher::enrich($eventMetadata, $event);
            $eventMetadata[MessageHeaders::MESSAGE_ID] ??= Uuid::uuid4()->toString();
            $eventMetadata[MessageHeaders::TIMESTAMP] ??= Clock::get()->now()->unixTime()->inSeconds();
            $eventMetadata = MessageHeaders::propagateContextHeaders([
                MessageHeaders::MESSAGE_ID => $message->getHeaders()->getMessageId(),
                MessageHeaders::MESSAGE_CORRELATION_ID => $message->getHeaders()->getCorrelationId(),
            ], $eventMetadata);

            return Event::createWithType(
                $eventMapper->mapEventToName($event),
                $event,
                $eventMetadata
            );
        }, $events);
    }

    public static function hasAnyIdentifiersDefined(array $aggregateIds): bool
    {
        return array_unique(array_values($aggregateIds)) !== [null];
    }

    public static function enrichAggregateEvents(array $events, int $versionBeforeHandling, array $identifiers, AggregateClassDefinition $aggregateDefinition): array
    {
        $incrementedVersion = $versionBeforeHandling;
        return array_map(static function (object $event) use (&$incrementedVersion, $identifiers, $aggregateDefinition): object {
            return $event->withAddedMetadata([
                MessageHeaders::EVENT_AGGREGATE_ID => count($identifiers) === 1 ? $identifiers[array_key_first($identifiers)] : $identifiers,
                MessageHeaders::EVENT_AGGREGATE_TYPE => $aggregateDefinition->getAggregateClassType(),
                MessageHeaders::EVENT_AGGREGATE_VERSION => ++$incrementedVersion,
            ]);
        }, $events);
    }
}
