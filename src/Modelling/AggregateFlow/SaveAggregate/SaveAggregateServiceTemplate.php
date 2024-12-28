<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Metadata\RevisionMetadataEnricher;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
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
        $aggregateIds = $metadata[AggregateMessage::AGGREGATE_ID] ?? [];
        if ($aggregateIds) {
            if (! is_array($aggregateIds)) {
                return [
                    array_key_first($aggregateDefinition->getAggregateIdentifierMapping()) => (string)$aggregateIds,
                ];
            }

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

        return AggregateIdResolver::resolveArrayOfIdentifiers(get_class($aggregate), $aggregateIds);
    }

    /**
     * @param array<string, string> $aggregateIds
     */
    public static function buildReplyMessage(
        bool $isFactoryMethod,
        array $aggregateIds,
        Message $message
    ): Message|null {
        if ($message->getHeaders()->containsKey(AggregateMessage::NULL_EXECUTION_RESULT)) {
            if ($message->getHeaders()->get(AggregateMessage::NULL_EXECUTION_RESULT)) {
                return null;
            }
        }

        if ($isFactoryMethod) {
            if (count($aggregateIds) === 1) {
                $aggregateIds = reset($aggregateIds);
            }

            $message = MessageBuilder::fromMessage($message)
                ->setPayload($aggregateIds)
                ->build();
        }

        return MessageBuilder::fromMessage($message)->build();
    }

    /**
     * @return Event[]
     */
    public static function buildEcotoneEvents(mixed $events, string $calledInterface, Message $message): array
    {
        Assert::isIterable($events, "Return value Event Sourced Aggregate {$calledInterface} must return array of events");
        $metadata = $message->getHeaders()->headers();

        return array_map(static function ($event) use ($message, $metadata, $calledInterface): Event {
            if (! is_object($event)) {
                $typeDescriptor = TypeDescriptor::createFromVariable($event);
                throw InvalidArgumentException::create("Events return by after calling {$calledInterface} must all be objects, {$typeDescriptor->toString()} given");
            }
            if ($event instanceof Event) {
                $metadata = $event->getMetadata();
                $event = $event->getPayload();
            }

            $metadata = MessageHeaders::unsetAllFrameworkHeaders($metadata);
            $metadata = RevisionMetadataEnricher::enrich($metadata, $event);
            $metadata[MessageHeaders::MESSAGE_ID] ??= Uuid::uuid4()->toString();
            $metadata[MessageHeaders::TIMESTAMP] ??= (int)round(microtime(true));
            $metadata = MessageHeaders::propagateContextHeaders([
                MessageHeaders::MESSAGE_ID => $message->getHeaders()->getMessageId(),
                MessageHeaders::MESSAGE_CORRELATION_ID => $message->getHeaders()->getCorrelationId(),
            ], $metadata);

            return Event::create($event, $metadata);
        }, $events);
    }
}
