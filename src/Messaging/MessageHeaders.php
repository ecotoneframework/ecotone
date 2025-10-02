<?php

namespace Ecotone\Messaging;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Scheduling\Clock;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Api\Distribution\DistributedBusHeader;
use Ecotone\Modelling\Config\MessageBusChannel;

use function json_encode;

use Ramsey\Uuid\Uuid;

/**
 * Class MessageHeaders
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
final class MessageHeaders
{
    /**
     * An identifier for this message instance. Changes each time a message is mutated.
     */
    public const MESSAGE_ID = 'id';
    /**
     * Used to correlate two or more messages.
     */
    public const MESSAGE_CORRELATION_ID = 'correlationId';
    /**
     * Used to point parent message
     */
    public const PARENT_MESSAGE_ID = 'parentId';
    /**
     * content-type values are parsed as media types, e.g., application/json or text/plain;charset=UTF-8
     */
    public const CONTENT_TYPE = 'contentType';
    /**
     * Type of object under payload
     */
    public const TYPE_ID = '__TypeId__';
    /**
     * Encoding of payload body
     */
    public const CONTENT_ENCODING = 'contentEncoding';
    /**
     * The time the message was created. Changes each time a message is mutated.
     */
    public const TIMESTAMP = 'timestamp';
    /**
     * List of channels comma separated, where to is next endpoint
     */
    public const ROUTING_SLIP = 'routingSlip';
    /**
     * A channel to which errors will be sent. It must represent a name from registry of a class implementing MessageChannel
     */
    public const REPLY_CHANNEL = 'replyChannel';
    /**
     * A channel to which errors will be sent. It must represent a name from registry of a class implementing MessageChannel
     */
    public const ERROR_CHANNEL = 'errorChannel';
    /**
     * Usually a sequence number with a group of messages with a SEQUENCE_SIZE
     */
    public const SEQUENCE_NUMBER = 'sequenceNumber';
    /**
     * The number of messages within a group of correlated messages.
     */
    public const SEQUENCE_SIZE = 'sequenceSize';
    /**
     * Message priority; for example within a PriorityChannel
     */
    public const PRIORITY = 'priority';
    /**
     * True if a message was detected as a duplicate by an idempotent receiver interceptor
     */
    public const DUPLICATE_MESSAGE = 'duplicateMessage';
    /**
     * Time to live of message in milliseconds
     */
    public const TIME_TO_LIVE = 'timeToLive';
    /**
     * Delivery delay in milliseconds
     */
    public const DELIVERY_DELAY = 'deliveryDelay';
    /**
     * Informs under which key acknowledge callback is stored for this consumer message
     */
    public const CONSUMER_ACK_HEADER_LOCATION = 'consumerAcknowledgeCallbackHeader';
    /**
     * Consumed channel name
     */
    public const POLLED_CHANNEL_NAME = 'polledChannelName';
    /**
     * Current polling metadata
     */
    public const CONSUMER_POLLING_METADATA = 'consumerPollingMetadata';
    /**
     * Expected content type of reply
     */
    public const REPLY_CONTENT_TYPE = 'replyContentType';
    /**
     * Revision number
     */
    public const REVISION = 'revision';

    public const STREAM_BASED_SOURCED = 'streamBasedSourced';

    public const CHANNEL_SEND_RETRY_NUMBER = 'channelSendRetryNumber';

    public const TEMPORARY_SPAN_CONTEXT_HEADER = 'ecotone.temporarySpanContext';

    public const EVENT_AGGREGATE_TYPE = '_aggregate_type';

    public const EVENT_AGGREGATE_ID = '_aggregate_id';

    public const EVENT_AGGREGATE_VERSION = '_aggregate_version';

    private array $headers;

    /**
     * MessageHeaders constructor.
     * @param array $headers
     */
    private function __construct(array $headers)
    {
        $this->initialize($headers);
    }

    /**
     * @return MessageHeaders|static
     */
    final public static function createEmpty(): self
    {
        return static::createMessageHeadersWith([]);
    }

    /**
     * @param array|string[] $headers
     * @return MessageHeaders|static
     */
    final public static function create(array $headers): self
    {
        return static::createMessageHeadersWith($headers);
    }

    final public function headers(): array
    {
        return $this->headers;
    }

    public static function getFrameworksHeaderNames(): array
    {
        return [
            self::MESSAGE_ID,
            self::MESSAGE_CORRELATION_ID,
            self::PARENT_MESSAGE_ID,
            self::CONTENT_TYPE,
            self::TYPE_ID,
            self::CONTENT_ENCODING,
            self::TIMESTAMP,
            self::ROUTING_SLIP,
            self::REPLY_CHANNEL,
            self::ERROR_CHANNEL,
            self::SEQUENCE_NUMBER,
            self::SEQUENCE_SIZE,
            self::PRIORITY,
            self::DUPLICATE_MESSAGE,
            self::TIME_TO_LIVE,
            self::DELIVERY_DELAY,
            self::POLLED_CHANNEL_NAME,
            self::REPLY_CONTENT_TYPE,
            self::STREAM_BASED_SOURCED,
            MessagingEntrypoint::ENTRYPOINT,
            self::CHANNEL_SEND_RETRY_NUMBER,
        ];
    }

    public static function unsetFrameworkKeys(array $metadata): array
    {
        foreach (self::getFrameworksHeaderNames() as $frameworksHeaderName) {
            unset($metadata[$frameworksHeaderName]);
        }

        return $metadata;
    }

    public static function unsetTransportMessageKeys(array $metadata): array
    {
        unset($metadata[self::MESSAGE_ID]);

        return $metadata;
    }

    public static function propagateContextHeaders(array $context, array $headers)
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

    public static function unsetCoreFrameworkHeaders(array $metadata): array
    {
        foreach (self::getFrameworksHeaderNames() as $frameworksHeaderName) {
            unset($metadata[$frameworksHeaderName]);
        }

        return $metadata;
    }

    public static function unsetAsyncKeys(array $metadata): array
    {
        unset($metadata[self::TYPE_ID]);

        return $metadata;
    }

    public static function unsetEnqueueMetadata(array $metadata): array
    {
        if (isset($metadata[self::CONSUMER_ACK_HEADER_LOCATION])) {
            unset($metadata[$metadata[self::CONSUMER_ACK_HEADER_LOCATION]]);
        }

        unset(
            $metadata[self::DELIVERY_DELAY],
            $metadata[self::TIME_TO_LIVE],
            $metadata[self::CONTENT_TYPE],
            $metadata[self::CONSUMER_ACK_HEADER_LOCATION],
            $metadata[self::POLLED_CHANNEL_NAME],
            $metadata[self::CONSUMER_POLLING_METADATA],
            $metadata[self::REPLY_CHANNEL],
            $metadata[self::TEMPORARY_SPAN_CONTEXT_HEADER],
        );

        return $metadata;
    }

    public static function unsetDistributionKeys(array $metadata): array
    {
        unset(
            $metadata[DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE],
            $metadata[DistributedBusHeader::DISTRIBUTED_ROUTING_KEY],
            $metadata[DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME],
            $metadata[DistributedBusHeader::DISTRIBUTED_TARGET_SERVICE_NAME],
        );

        return $metadata;
    }

    public static function unsetBusKeys(array $metadata): array
    {
        unset(
            $metadata[MessageBusChannel::COMMAND_CHANNEL_NAME_BY_NAME],
            $metadata[MessageBusChannel::COMMAND_CHANNEL_NAME_BY_OBJECT],
            $metadata[MessageBusChannel::EVENT_CHANNEL_NAME_BY_NAME],
            $metadata[MessageBusChannel::EVENT_CHANNEL_NAME_BY_OBJECT],
            $metadata[MessageBusChannel::QUERY_CHANNEL_NAME_BY_NAME],
            $metadata[MessageBusChannel::QUERY_CHANNEL_NAME_BY_OBJECT]
        );

        return $metadata;
    }

    public static function unsetAggregateKeys(array $metadata): array
    {
        unset(
            $metadata[AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER],
            $metadata[AggregateMessage::CALLED_AGGREGATE_INSTANCE],
            $metadata[AggregateMessage::CALLED_AGGREGATE_CLASS],
            $metadata[AggregateMessage::RECORDED_AGGREGATE_EVENTS],
            $metadata[AggregateMessage::TARGET_VERSION],
            $metadata[AggregateMessage::NULL_EXECUTION_RESULT],
        );

        return $metadata;
    }

    public static function unsetNonUserKeys(array $metadata): array
    {
        $metadata = self::unsetEnqueueMetadata($metadata);
        $metadata = self::unsetDistributionKeys($metadata);
        $metadata = self::unsetAsyncKeys($metadata);
        $metadata = self::unsetBusKeys($metadata);

        return self::unsetAggregateKeys($metadata);
    }

    /**
     * @param string $headerRegex e.g. ecotone-domain-*
     *
     * @return array
     */
    final public function findByRegex(string $headerRegex): array
    {
        $foundHeaders = [];
        $headerRegex = str_replace('.', "\.", $headerRegex);
        $headerRegex = str_replace('*', '.*', $headerRegex);
        $headerRegex = '#' . $headerRegex . '#';

        foreach ($this->headers as $key => $value) {
            if (preg_match($headerRegex, $key)) {
                $foundHeaders[$key] = $value;
            }
        }

        return $foundHeaders;
    }

    /**
     * @param string $headerName
     * @return bool
     */
    final public function containsKey(string $headerName): bool
    {
        return array_key_exists($headerName, $this->headers);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    final public function containsValue($value): bool
    {
        return in_array($value, $this->headers);
    }

    /**
     * @param string $headerName
     * @return mixed
     * @throws MessagingException
     */
    final public function get(string $headerName)
    {
        if (! $this->containsKey($headerName)) {
            throw MessageHeaderDoesNotExistsException::create("Header with name {$headerName} does not exists");
        }

        return $this->headers[$headerName];
    }

    /**
     * @return int
     */
    final public function size(): int
    {
        return count($this->headers());
    }

    /**
     * @param MessageHeaders $messageHeaders
     * @return bool
     */
    final public function equals(MessageHeaders $messageHeaders): bool
    {
        return $this == $messageHeaders;
    }

    /**
     * @return mixed
     */
    final public function getReplyChannel()
    {
        return $this->get(self::REPLY_CHANNEL);
    }

    /**
     * @return mixed
     */
    final public function getErrorChannel()
    {
        return $this->get(self::ERROR_CHANNEL);
    }

    /**
     * @param string $messageId
     * @return bool
     */
    final public function hasMessageId(string $messageId): bool
    {
        return $this->get(self::MESSAGE_ID) === $messageId;
    }

    /**
     * @param string $headerName
     * @param $headerValue
     */
    final protected function changeHeader(string $headerName, $headerValue): void
    {
        $this->headers[$headerName] = $headerValue;
    }

    /**
     * @return bool
     */
    public function hasContentType(): bool
    {
        return $this->containsKey(self::CONTENT_TYPE);
    }

    public function hasReplyChannel(): bool
    {
        return $this->containsKey(self::REPLY_CHANNEL);
    }

    /**
     * @return string
     * @throws MessagingException
     */
    public function getMessageId(): string
    {
        return $this->get(MessageHeaders::MESSAGE_ID);
    }

    public function getCorrelationId(): string
    {
        return $this->get(MessageHeaders::MESSAGE_CORRELATION_ID);
    }

    public function getParentId(): ?string
    {
        return $this->containsKey(MessageHeaders::PARENT_MESSAGE_ID)
            ? $this->get(MessageHeaders::PARENT_MESSAGE_ID)
            : null;
    }

    /**
     * @return int
     * @throws MessagingException
     */
    public function getTimestamp(): int
    {
        return $this->get(MessageHeaders::TIMESTAMP);
    }

    /**
     * @return MediaType
     * @throws MessagingException
     * @throws Support\InvalidArgumentException
     */
    public function getContentType(): MediaType
    {
        return MediaType::parseMediaType($this->get(self::CONTENT_TYPE));
    }

    /**
     * @param array|string[] $headers
     * @throws MessagingException
     */
    private function initialize(array $headers): void
    {
        foreach ($headers as $headerName => $headerValue) {
            if (is_null($headerName) || $headerName === '') {
                throw InvalidMessageHeaderException::create('Passed empty header name');
            }
            if (! is_string($headerName)) {
                throw InvalidMessageHeaderException::create(sprintf('Passed header name must be string `%s` given, with value `%s`', $headerName, $headerValue));
            }
        }

        $this->headers = $headers;
    }

    /**
     * @param array $headers
     * @return MessageHeaders
     */
    private static function createMessageHeadersWith(array $headers): MessageHeaders
    {
        if (! array_key_exists(self::MESSAGE_ID, $headers)) {
            $headers[self::MESSAGE_ID] = Uuid::uuid4()->toString();
        }
        if (! array_key_exists(self::TIMESTAMP, $headers)) {
            $headers[self::TIMESTAMP] = Clock::get()->now()->unixTime()->inSeconds();
        } else {
            $headers[self::TIMESTAMP] = (int)$headers[self::TIMESTAMP];
            if ($headers[self::TIMESTAMP] === 0) {
                $headers[self::TIMESTAMP] = Clock::get()->now()->unixTime()->inSeconds();
            }
        }
        if (! array_key_exists(self::MESSAGE_CORRELATION_ID, $headers)) {
            $headers[self::MESSAGE_CORRELATION_ID] = Uuid::uuid4()->toString();
        }

        return new static($headers);
    }

    /**
     * @return false|string
     * @throws Handler\TypeDefinitionException
     * @throws MessagingException
     */
    public function __toString()
    {
        return json_encode($this->convertToScalarsIfPossible($this->headers));
    }

    /**
     * @param iterable $dataToConvert
     * @return array
     * @throws Handler\TypeDefinitionException
     * @throws MessagingException
     */
    private function convertToScalarsIfPossible(iterable $dataToConvert): array
    {
        $data = [];

        foreach ($dataToConvert as $headerName => $header) {
            if (Type::createFromVariable($header)->isScalar()) {
                $data[$headerName] = $header;
                continue;
            }

            if (is_iterable($header)) {
                $data[$headerName] = $this->convertToScalarsIfPossible($header);
            } elseif (is_object($header) && method_exists($header, '__toString')) {
                $data[$headerName] = (string)$header;
            }
        }

        return $data;
    }

    /**
     * @param string[]
     */
    public function resolveRoutingSlip(): array
    {
        $routingSlip = $this->containsKey(MessageHeaders::ROUTING_SLIP) ? $this->get(MessageHeaders::ROUTING_SLIP) : [];
        if ($routingSlip === []) {
            throw MessagingException::create('Can not resolve routing slip, as it is not set');
        }

        return explode(',', $routingSlip);
    }
}
