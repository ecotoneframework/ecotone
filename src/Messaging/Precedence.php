<?php

declare(strict_types=1);

namespace Ecotone\Messaging;

/**
 * The lower value, the quicker interceptor will be run
 */
/**
 * licence Apache-2.0
 */
interface Precedence
{
    /**
     * Tracing hooks in at this level
     */
    public const TRACING_PRECEDENCE = Precedence::ERROR_CHANNEL_PRECEDENCE - 101;
    /**
     * Message consumer polling interceptors are run at this precedence
     */
    public const ASYNCHRONOUS_CONSUMER_INTERCEPTOR_PRECEDENCE = Precedence::ERROR_CHANNEL_PRECEDENCE - 100;
    /**
     * Message consumer acknowledge message at this precedence
     */
    public const MESSAGE_ACKNOWLEDGE_PRECEDENCE = Precedence::ERROR_CHANNEL_PRECEDENCE - 99;
    /**
     * If errors are not send to error channel, they are logged at this precedence
     */
    public const EXCEPTION_LOGGING_PRECEDENCE = Precedence::ERROR_CHANNEL_PRECEDENCE - 1;
    /**
     * Errors are send to error channel at this precedence
     */
    public const ERROR_CHANNEL_PRECEDENCE = -1000000;

    public const SYSTEM_PRECEDENCE_BEFORE = -1001;
    public const SYSTEM_PRECEDENCE_AFTER = 1001;

    /**
     * Endpoint headers like delivery delay, priority, time to live
     */
    public const ENDPOINT_HEADERS_PRECEDENCE = -3000;

    /**
     * Custom retries taking precedence over global to allow overriding configuration
     */
    public const CUSTOM_INSTANT_RETRY_PRECEDENCE = -2003;

    /**
     * Retrying is executed before transactions, as we want to retry completely from the beginning (for example to recover from mysql gone away)
     */
    public const GLOBAL_INSTANT_RETRY_PRECEDENCE = -2002;

    public const BETWEEN_INSTANT_RETRY_AND_TRANSACTION_PRECEDENCE = -2001;

    /**
     * Database transactions are started at this precedence
     */
    public const DATABASE_TRANSACTION_PRECEDENCE = -2000;

    /**
     * Collects messages to be sent to asynchronous channels.
     */
    public const COLLECTOR_SENDER_PRECEDENCE = self::DATABASE_TRANSACTION_PRECEDENCE + 1;

    public const DATABASE_OBJECT_MANAGER_PRECEDENCE = self::COLLECTOR_SENDER_PRECEDENCE + 1;

    /**
     * Lazy events are published at this precedence
     */
    public const LAZY_EVENT_PUBLICATION_PRECEDENCE = -1900;

    public const DEFAULT_PRECEDENCE = 1;

    public const AGGREGATE_MESSAGE_PAYLOAD_CONVERTER = Precedence::DEFAULT_PRECEDENCE + 10000;
}
