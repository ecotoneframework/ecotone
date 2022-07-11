<?php

declare(strict_types=1);

namespace Ecotone\Amqp;

/**
 * Interface AmqpHeader
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AmqpHeader
{
    public const PREFIX = 'amqp_';
    public const APP_ID = 'amqp_appId';
    public const CLUSTER_ID = 'amqp_clusterId';
    public const CONTENT_ENCODING = 'amqp_contentEncoding';
    public const CONTENT_LENGTH = 'amqp_contentLength';
    public const CONTENT_TYPE = 'contentType';
    public const CORRELATION_ID = 'amqp_correlationId';
    public const DELAY = 'amqp_delay';
    public const DELIVERY_MODE = 'amqp_deliveryMode';
    public const DELIVERY_TAG = 'amqp_deliveryTag';
    public const EXPIRATION = 'amqp_expiration';
    public const MESSAGE_COUNT = 'amqp_messageCount';
    public const MESSAGE_ID = 'amqp_messageId';
    public const RECEIVED_DELAY = 'amqp_receivedDelay';
    public const RECEIVED_DELIVERY_MODE = 'amqp_receivedDeliveryMode';
    public const RECEIVED_EXCHANGE = 'amqp_receivedExchange';
    public const RECEIVED_ROUTING_KEY = 'amqp_receivedRoutingKey';
    public const RECEIVED_USER_ID = 'amqp_receivedUserId';
    public const REDELIVERED = 'amqp_redelivered';
    public const REPLY_TO = 'amqp_replyTo';
    public const TIMESTAMP = 'amqp_timestamp';
    public const TYPE = 'amqp_type';
    public const USER_ID = 'amqp_userId';
    public const PUBLISH_CONFIRM = 'amqp_publishConfirm';
    public const PUBLISH_CONFIRM_NACK_CAUSE = 'amqp_publishConfirmNackCause';
    public const RETURN_REPLY_CODE = 'amqp_returnReplyCode';
    public const RETURN_REPLY_TEXT = 'amqp_returnReplyText';
    public const RETURN_EXCHANGE = 'amqp_returnExchange';
    public const RETURN_ROUTING_KEY = 'amqp_returnRoutingKey';
    public const CHANNEL = 'amqp_channel';
    public const CONSUMER_TAG = 'amqp_consumerTag';
    public const CONSUMER_QUEUE = 'amqp_consumerQueue';

    public const HEADER_CONSUMER = 'amqp_originalConsumer';
    public const HEADER_AMQP_MESSAGE = 'amqp_originalMessage';
    public const HEADER_ACKNOWLEDGE = 'amqp_acknowledge';
    public const HEADER_RELATED_AMQP_LIB_CHANNEL = 'amqp_libChannel';
}
