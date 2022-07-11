<?php

declare(strict_types=1);

namespace Ecotone\Messaging;

interface Precedence
{
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
     * Database transactions are started at this precedence
     */
    public const DATABASE_TRANSACTION_PRECEDENCE = -2000;
    /**
     * Lazy events are published at this precedence
     */
    public const LAZY_EVENT_PUBLICATION_PRECEDENCE = -1900;

    public const DEFAULT_PRECEDENCE = 1;

    public const AGGREGATE_MESSAGE_PAYLOAD_CONVERTER = Precedence::DEFAULT_PRECEDENCE + 10000;

    public const GATEWAY_REPLY_CONVERSION_PRECEDENCE = 1000000;
}
