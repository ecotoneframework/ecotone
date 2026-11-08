<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging;

use RuntimeException;
use Throwable;

/**
 * licence Apache-2.0
 */
final class DatabaseTransactionLost extends RuntimeException
{
    public static function whileSendingTo(string $channelName, Throwable $cause): self
    {
        return new self(
            "The database connection was lost while a message was being sent to `{$channelName}` inside a database transaction, so the message was not stored. "
            . 'It is not retried in place: the transaction it belonged to is gone, and a retry would store the message on a new connection, outside that transaction. '
            . "The transaction rolls back and the command or consumed message is retried as a whole: the consumer redelivers an asynchronous endpoint's message, and the command bus instant retries, on by default when DbalConfiguration runs the command bus in a transaction, retry a command. "
            . 'If they were turned off, turn them back on with InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true), and if they list their retry exceptions, include DatabaseTransactionLost::class. '
            . 'A console command run in a transaction (DbalConfiguration::withTransactionOnConsoleCommands) and a transaction the application opened itself are not retried by Ecotone: run the command again, or retry the work that opened the transaction. '
            . 'To store the message outside the transaction and retry it in place instead, run its handler without a database transaction: mark the handler with #[WithoutDatabaseTransaction] (Ecotone\\Api\\Attribute\\WithoutDatabaseTransaction), or turn the transaction off with DbalConfiguration::createWithDefaults()->withTransactionOnCommandBus(false), ->withTransactionOnAsynchronousEndpoints(false) or ->withTransactionOnConsoleCommands(false).',
            0,
            $cause,
        );
    }

    public static function whileReconnectingInsideTransaction(): self
    {
        return new self(
            'The database connection was lost while a message was being handled inside a database transaction, so Ecotone refuses to reconnect until the message ends. '
            . 'A new connection would be outside your transaction: part of the message\'s data could be stored while the rest is rolled back, and stored again when the message is retried. '
            . 'The message is rolled back and retried as a whole: the consumer redelivers an asynchronous endpoint\'s message, and the command bus instant retries, on by default when DbalConfiguration runs the command bus in a transaction, retry a command. '
            . 'If they were turned off, turn them back on with InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true), and if they list their retry exceptions, include DatabaseTransactionLost::class. '
            . 'If the connection failure was first caught and ignored — for example by a middleware in your codebase, or a library that retries automatically — let it reach Ecotone instead. '
            . 'A console command run in a transaction (DbalConfiguration::withTransactionOnConsoleCommands) is not retried by Ecotone: run the command again. '
            . 'To allow the reconnect instead, turn the protection off with DbalConfiguration::createWithDefaults()->withTransactionReconnectProtection(TransactionReconnectProtection::IGNORE) (Ecotone\\Api\\Dbal\\ExtensionObject\\TransactionReconnectProtection), or run the handler without a database transaction: #[WithoutDatabaseTransaction] (Ecotone\\Api\\Attribute\\WithoutDatabaseTransaction), or ->withTransactionOnCommandBus(false), ->withTransactionOnAsynchronousEndpoints(false) or ->withTransactionOnConsoleCommands(false).',
        );
    }
}
