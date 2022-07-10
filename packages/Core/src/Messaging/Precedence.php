<?php
declare(strict_types=1);

namespace Ecotone\Messaging;

interface Precedence
{
    /**
     * Message consumer polling interceptors are run at this precedence
     */
    const ASYNCHRONOUS_CONSUMER_INTERCEPTOR_PRECEDENCE = Precedence::ERROR_CHANNEL_PRECEDENCE - 100;
    /**
     * Message consumer acknowledge message at this precedence
     */
    const MESSAGE_ACKNOWLEDGE_PRECEDENCE = Precedence::ERROR_CHANNEL_PRECEDENCE - 99;
    /**
     * If errors are not send to error channel, they are logged at this precedence
     */
    const EXCEPTION_LOGGING_PRECEDENCE = Precedence::ERROR_CHANNEL_PRECEDENCE - 1;
    /**
     * Errors are send to error channel at this precedence
     */
    const ERROR_CHANNEL_PRECEDENCE = -1000000;

    const SYSTEM_PRECEDENCE_BEFORE = -1001;
    const SYSTEM_PRECEDENCE_AFTER = 1001;

    /**
     * Endpoint headers like delivery delay, priority, time to live
     */
    const ENDPOINT_HEADERS_PRECEDENCE = -3000;
    /**
     * Database transactions are started at this precedence
     */
    const DATABASE_TRANSACTION_PRECEDENCE = -2000;
    /**
     * Lazy events are published at this precedence
     */
    const LAZY_EVENT_PUBLICATION_PRECEDENCE = -1900;

    const DEFAULT_PRECEDENCE = 1;

    const AGGREGATE_MESSAGE_PAYLOAD_CONVERTER = Precedence::DEFAULT_PRECEDENCE + 10000;

    const GATEWAY_REPLY_CONVERSION_PRECEDENCE = 1000000;
}