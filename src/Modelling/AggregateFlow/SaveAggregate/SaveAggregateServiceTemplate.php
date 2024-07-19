<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateIdResolver;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\NoAggregateFoundToBeSaved;
use Ecotone\Modelling\NoCorrectIdentifierDefinedException;

/**
 * licence Apache-2.0
 */
class SaveAggregateServiceTemplate
{
    public static function resolveAggregate(
        string  $calledClass,
        Message $message,
        bool    $isFactoryMethod
    ): object|string {
        $messageHeaders = $message->getHeaders();
        if ($isFactoryMethod && $messageHeaders->containsKey(AggregateMessage::RESULT_AGGREGATE_OBJECT)) {
            return $messageHeaders->get(AggregateMessage::RESULT_AGGREGATE_OBJECT);
        }
        if ($messageHeaders->containsKey(AggregateMessage::CALLED_AGGREGATE_OBJECT)) {
            return $messageHeaders->get(AggregateMessage::CALLED_AGGREGATE_OBJECT);
        }

        throw NoAggregateFoundToBeSaved::create("After calling {$calledClass} no aggregate was found to be saved.");
    }

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
        string $calledClass,
        array $aggregateIdentifierMapping,
        array $aggregateIdentifierGetMethods,
        object|string $aggregate,
        bool $throwOnNoIdentifier
    ): array {
        $aggregateIds = $metadata[AggregateMessage::AGGREGATE_ID] ?? [];
        if ($aggregateIds) {
            return $aggregateIds;
        }

        foreach ($aggregateIdentifierMapping as $aggregateIdName => $aggregateIdValue) {
            if (isset($aggregateIdentifierGetMethods[$aggregateIdName])) {
                $id = call_user_func([$aggregate, $aggregateIdentifierGetMethods[$aggregateIdName]]);

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

                throw NoCorrectIdentifierDefinedException::create("After calling {$calledClass} has no identifier assigned. If you're using Event Sourcing Aggregate, please set up #[EventSourcingHandler] that will assign the id after first event");
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
    ): \Ecotone\Messaging\Support\GenericMessage {
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
}
