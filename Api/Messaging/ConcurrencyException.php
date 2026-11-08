<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging;

use Ecotone\Messaging\MessagingException;

use function sprintf;

/**
 * licence Apache-2.0
 */
class ConcurrencyException extends MessagingException
{
    public static function forStaleAggregate(string $aggregateType, string $aggregateId, int $expectedVersion, int $currentVersion): static
    {
        return static::create(sprintf(
            'Aggregate %s %s was loaded at version %d, but %s: another transaction saved it after it was loaded, '
            . 'or an earlier save in the same transaction did (for example a command sent from inside the handler whose own handler saves the same aggregate). '
            . 'Load it again and retry the command against its current version: send the command again, '
            . 'or let Ecotone retry it: the command bus and asynchronous endpoints retry by default when DbalConfiguration runs them in a database transaction, '
            . 'and without one InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true, 3, [ConcurrencyException::class]) enables it. '
            . 'A save made earlier in the same transaction fails on every retry, so decide both in one handler instead.',
            $aggregateType,
            $aggregateId,
            $expectedVersion,
            self::whereItIsNow($expectedVersion, $currentVersion),
        ));
    }

    public static function forStaleDocument(string $collectionName, string $documentId, int $expectedVersion, int $currentVersion): static
    {
        return static::create(sprintf(
            'Document %s in collection %s was expected at version %d, but %s (version 0 means it is not stored): '
            . 'another write changed it after it was read. Read it again with DocumentStore::getDocument() and DocumentStore::getDocumentVersion(), '
            . 'apply the change to what is stored now and write it under that version, '
            . 'or pass DocumentStore::LAST_WRITE_WINS as the expected version when this write should replace whatever is stored.',
            $documentId,
            $collectionName,
            $expectedVersion,
            self::whereItIsNow($expectedVersion, $currentVersion),
        ));
    }

    public static function forRecordedAggregateVersion(string $streamName, string $aggregateType, string $aggregateId, string $aggregateVersion): self
    {
        return new self(sprintf(
            'Aggregate %s %s already has version %s recorded in stream %s, so nothing from this append was recorded: '
            . 'an aggregate type, id and version are recorded once per stream. '
            . 'Another save of the same aggregate recorded that version first. Load the aggregate again and retry the command against its current version: '
            . 'send the command again, or let Ecotone retry it: the command bus retries by default when DbalConfiguration runs it in a database transaction, '
            . 'and without one InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true, 3, [ConcurrencyException::class]) enables it. '
            . 'When appending through EventStore::appendTo() directly, give each event of the aggregate the next free MessageHeaders::EVENT_AGGREGATE_VERSION.',
            $aggregateType,
            $aggregateId,
            $aggregateVersion,
            $streamName,
        ), self::errorCode());
    }

    public static function forRecordedEventId(string $streamName, string $eventId): self
    {
        return new self(sprintf(
            'Event id %s is already recorded in stream %s, so nothing from this append was recorded: an event id is recorded once per stream. '
            . 'Give each appended event its own MessageHeaders::MESSAGE_ID, or leave it out and an id is generated for it.',
            $eventId,
            $streamName,
        ), self::errorCode());
    }

    public static function forHandledMessage(string $messageId, string $consumerEndpointId, string $routingSlip): self
    {
        return new self(sprintf(
            'Message %s was already recorded as handled (consumer endpoint "%s", routing slip "%s"): another handling of the same message recorded it first, '
            . 'so this handling is refused instead of recording it twice. Deduplication lets the first one through; when this one is retried it is skipped as already handled, '
            . 'so nothing needs changing unless the same message id is reused for different messages - then give each message its own MessageHeaders::MESSAGE_ID, '
            . 'or name what identifies it with #[Deduplicated(expression: ...)].',
            $messageId,
            $consumerEndpointId,
            $routingSlip,
        ), self::errorCode());
    }

    public static function forStaleProjection(string $projectionName, ?string $partitionKey, int $loadedVersion): self
    {
        return new self(sprintf(
            "Projection '%s' (partition: %s) was loaded at version %d, but another run of the same partition saved it first. "
            . 'Nothing this run projected was saved: its transaction is rolled back, and the events are projected from the position the other run stored when it runs again. '
            . 'For a synchronous projection that run is the command, for an asynchronous one the message: the command bus and asynchronous endpoints retry by default when DbalConfiguration runs them in a database transaction, '
            . 'and an asynchronous projection is always retried. Without one, enable InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true, 3, [ConcurrencyException::class]), '
            . 'or send the command again.',
            $projectionName,
            $partitionKey ?? 'global',
            $loadedVersion,
        ), self::errorCode());
    }

    private static function whereItIsNow(int $expectedVersion, int $currentVersion): string
    {
        return $currentVersion === $expectedVersion
            ? 'it has been saved since and this transaction still reads the earlier version (a REPEATABLE READ snapshot, as on MySQL and MariaDB)'
            : sprintf('it is at version %d now', $currentVersion);
    }

    protected static function errorCode(): int
    {
        return self::MESSAGE_HANDLING_EXCEPTION;
    }
}
