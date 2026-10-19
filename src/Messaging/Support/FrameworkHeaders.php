<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Support;

use Ecotone\Api\Gateway\DistributedBusHeader;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\EventSourcing\Tagging\MatchedTagSequences;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Config\MessageBusChannel;
use Ecotone\Modelling\DecisionModel\DecisionModelLoadedState;

/**
 * licence Apache-2.0
 */
final class FrameworkHeaders
{
    public static function propagateContextHeaders(array $context, array $headers): array
    {
        $headers = array_merge($context, $headers);
        if (array_key_exists(MessageHeaders::MESSAGE_CORRELATION_ID, $context)) {
            $headers[MessageHeaders::MESSAGE_CORRELATION_ID] = $context[MessageHeaders::MESSAGE_CORRELATION_ID];
        }
        if (array_key_exists(MessageHeaders::MESSAGE_CORRELATION_ID, $context) && $headers[MessageHeaders::MESSAGE_ID] !== $context[MessageHeaders::MESSAGE_ID]) {
            $headers[MessageHeaders::PARENT_MESSAGE_ID] = $context[MessageHeaders::MESSAGE_ID];
        }

        return $headers;
    }

    public static function unsetAllFrameworkHeaders(array $metadata): array
    {
        $metadata =  self::unsetCoreFrameworkHeaders($metadata);
        $metadata = self::unsetAsyncKeys($metadata);
        $metadata = self::unsetEnqueueMetadata($metadata);
        $metadata = self::unsetDistributionKeys($metadata);
        $metadata = self::unsetBusKeys($metadata);

        return self::unsetAggregateKeys($metadata);
    }

    public static function unsetNonUserKeys(array $metadata): array
    {
        $metadata = self::unsetEnqueueMetadata($metadata);
        $metadata = self::unsetDistributionKeys($metadata);
        $metadata = self::unsetAsyncKeys($metadata);
        $metadata = self::unsetBusKeys($metadata);

        return self::unsetAggregateKeys($metadata);
    }

    public static function unsetEnqueueMetadata(array $metadata): array
    {
        if (isset($metadata[MessageHeaders::CONSUMER_ACK_HEADER_LOCATION])) {
            unset($metadata[$metadata[MessageHeaders::CONSUMER_ACK_HEADER_LOCATION]]);
        }

        unset(
            $metadata[MessageHeaders::DELIVERY_DELAY],
            $metadata[MessageHeaders::TIME_TO_LIVE],
            $metadata[MessageHeaders::CONTENT_TYPE],
            $metadata[MessageHeaders::CONSUMER_ACK_HEADER_LOCATION],
            $metadata[MessageHeaders::POLLED_CHANNEL_NAME],
            $metadata[MessageHeaders::INBOUND_REQUEST_CHANNEL],
            $metadata[MessageHeaders::CONSUMER_POLLING_METADATA],
            $metadata[MessageHeaders::REPLY_CHANNEL],
        );

        return $metadata;
    }

    public static function unsetAggregateKeys(array $metadata): array
    {
        unset(
            $metadata[AggregateMessage::CALLED_AGGREGATE_INSTANCE],
            $metadata[AggregateMessage::CALLED_AGGREGATE_CLASS],
            $metadata[AggregateMessage::CALLED_AGGREGATE_COUNTER_CAPTURED_AT_LOAD],
            $metadata[AggregateMessage::RECORDED_AGGREGATE_EVENTS],
            $metadata[AggregateMessage::TARGET_VERSION],
            $metadata[AggregateMessage::NULL_EXECUTION_RESULT],
            $metadata[DecisionModelLoadedState::HEADER_NAME],
            $metadata[MatchedTagSequences::HEADER_NAME],
        );

        return $metadata;
    }

    private static function unsetCoreFrameworkHeaders(array $metadata): array
    {
        foreach (self::getFrameworksHeaderNames() as $frameworksHeaderName) {
            unset($metadata[$frameworksHeaderName]);
        }

        return $metadata;
    }

    private static function unsetAsyncKeys(array $metadata): array
    {
        unset($metadata[MessageHeaders::TYPE_ID]);

        return $metadata;
    }

    private static function unsetDistributionKeys(array $metadata): array
    {
        unset(
            $metadata[DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE],
            $metadata[DistributedBusHeader::DISTRIBUTED_ROUTING_KEY],
            $metadata[DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME],
            $metadata[DistributedBusHeader::DISTRIBUTED_TARGET_SERVICE_NAME],
        );

        return $metadata;
    }

    private static function unsetBusKeys(array $metadata): array
    {
        unset(
            $metadata[MessageBusChannel::COMMAND_CHANNEL_NAME_BY_NAME],
            $metadata[MessageBusChannel::EVENT_CHANNEL_NAME_BY_NAME],
            $metadata[MessageBusChannel::QUERY_CHANNEL_NAME_BY_NAME]
        );

        return $metadata;
    }

    private static function getFrameworksHeaderNames(): array
    {
        return [
            MessageHeaders::MESSAGE_ID,
            MessageHeaders::MESSAGE_CORRELATION_ID,
            MessageHeaders::PARENT_MESSAGE_ID,
            MessageHeaders::CONTENT_TYPE,
            MessageHeaders::TYPE_ID,
            MessageHeaders::CONTENT_ENCODING,
            MessageHeaders::TIMESTAMP,
            MessageHeaders::ROUTING_SLIP,
            MessageHeaders::REPLY_CHANNEL,
            MessageHeaders::ERROR_CHANNEL,
            MessageHeaders::SEQUENCE_NUMBER,
            MessageHeaders::SEQUENCE_SIZE,
            MessageHeaders::PRIORITY,
            MessageHeaders::DUPLICATE_MESSAGE,
            MessageHeaders::TIME_TO_LIVE,
            MessageHeaders::DELIVERY_DELAY,
            MessageHeaders::POLLED_CHANNEL_NAME,
            MessageHeaders::INBOUND_REQUEST_CHANNEL,
            MessageHeaders::REPLY_CONTENT_TYPE,
            MessagingEntrypointService::ENTRYPOINT,
            MessageHeaders::TRACING_ID,
            MessageHeaders::TRACEPARENT,
            MessageHeaders::TRACESTATE,
            MessageHeaders::TRACING_DELIVERY,
        ];
    }
}
