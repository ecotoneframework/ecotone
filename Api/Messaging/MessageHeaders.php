<?php

namespace Ecotone\Api\Messaging;

use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\InvalidMessageHeaderException;
use Ecotone\Messaging\MessageHeaderDoesNotExistsException;
use Ecotone\Messaging\MessagingException;

use function json_encode;

use Symfony\Component\Uid\Uuid;

/**
 * licence Apache-2.0
 *
 * @link https://docs.ecotone.tech/modelling/extending-messaging-middlewares/message-headers
 */
final class MessageHeaders
{
    /**
     * An identifier for this message instance. Changes each time a message is mutated.
     */
    public const MESSAGE_ID = 'id';
    public const MESSAGE_CORRELATION_ID = 'correlationId';
    public const PARENT_MESSAGE_ID = 'parentId';
    /**
     * content-type values are parsed as media types, e.g., application/json or text/plain;charset=UTF-8
     */
    public const CONTENT_TYPE = 'contentType';
    public const TYPE_ID = '__TypeId__';
    public const CONTENT_ENCODING = 'contentEncoding';
    /**
     * The time the message was created. Changes each time a message is mutated.
     */
    public const TIMESTAMP = 'timestamp';
    /**
     * List of channels comma separated, where to is next endpoint
     */
    public const ROUTING_SLIP = 'routingSlip';
    public const REPLY_CHANNEL = 'replyChannel';
    public const ERROR_CHANNEL = 'errorChannel';
    public const SEQUENCE_NUMBER = 'sequenceNumber';
    public const SEQUENCE_SIZE = 'sequenceSize';
    public const PRIORITY = 'priority';
    public const DUPLICATE_MESSAGE = 'duplicateMessage';
    /**
     * Time to live of message in milliseconds
     */
    public const TIME_TO_LIVE = 'timeToLive';
    /**
     * Delivery delay in milliseconds
     */
    public const DELIVERY_DELAY = 'deliveryDelay';
    public const CONSUMER_ACK_HEADER_LOCATION = 'consumerAcknowledgeCallbackHeader';
    /**
     * Consumed channel name (set when the Message originates from a pollable Message Channel)
     */
    public const POLLED_CHANNEL_NAME = 'polledChannelName';
    /**
     * Inbound Channel Adapter request channel name (set when the Message originates from an Inbound Channel Adapter
     * such as #[KafkaConsumer], AMQP inbound, #[Scheduled]). Carries the user-facing request channel where the Message
     * is dispatched after polling, so it can be replayed back to the same handler.
     */
    public const INBOUND_REQUEST_CHANNEL = 'inboundRequestChannel';
    public const CONSUMER_POLLING_METADATA = 'consumerPollingMetadata';
    public const REPLY_CONTENT_TYPE = 'replyContentType';
    public const REVISION = 'revision';

    public const STREAM_BASED_SOURCED = 'streamBasedSourced';

    public const CHANNEL_SEND_RETRY_NUMBER = 'channelSendRetryNumber';

    public const TEMPORARY_SPAN_CONTEXT_HEADER = 'ecotone.temporarySpanContext';

    public const EVENT_AGGREGATE_TYPE = '_aggregate_type';

    public const EVENT_AGGREGATE_ID = '_aggregate_id';

    public const EVENT_AGGREGATE_VERSION = '_aggregate_version';

    public const EVENT_STREAM_NAME = '_stream_name';

    private array $headers;

    private function __construct(array $headers)
    {
        $this->initialize($headers);
    }

    /**
     * @return MessageHeaders|static
     */
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

    final public function containsKey(string $headerName): bool
    {
        return array_key_exists($headerName, $this->headers);
    }

    /**
     * @param mixed $value
     */
    final public function containsValue($value): bool
    {
        return in_array($value, $this->headers);
    }

    /**
     * @return mixed
     */
    final public function get(string $headerName)
    {
        if (! $this->containsKey($headerName)) {
            throw MessageHeaderDoesNotExistsException::create("Header with name {$headerName} does not exists");
        }

        return $this->headers[$headerName];
    }

    final public function size(): int
    {
        return count($this->headers());
    }

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

    final public function hasMessageId(string $messageId): bool
    {
        return $this->get(self::MESSAGE_ID) === $messageId;
    }

    /**
     * @param $headerValue
     */
    final protected function changeHeader(string $headerName, $headerValue): void
    {
        $this->headers[$headerName] = $headerValue;
    }

    public function hasContentType(): bool
    {
        return $this->containsKey(self::CONTENT_TYPE);
    }

    public function hasReplyChannel(): bool
    {
        return $this->containsKey(self::REPLY_CHANNEL);
    }

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

    public function getTimestamp(): int
    {
        return $this->get(MessageHeaders::TIMESTAMP);
    }

    public function getContentType(): MediaType
    {
        return MediaType::parseMediaType($this->get(self::CONTENT_TYPE));
    }

    /**
     * @param array|string[] $headers
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

    private static function createMessageHeadersWith(array $headers): MessageHeaders
    {
        if (! array_key_exists(self::MESSAGE_ID, $headers)) {
            $headers[self::MESSAGE_ID] = Uuid::v7()->toRfc4122();
        }
        $headers[self::TIMESTAMP] = (int) ($headers[self::TIMESTAMP] ?? 0);
        if ($headers[self::TIMESTAMP] === 0) {
            throw InvalidMessageHeaderException::create('Message headers have no timestamp. Build messages with MessageBuilder, the container service that stamps them from EcotoneClockInterface; in tests use Ecotone\\Test\\StaticMessageBuilder.');
        }
        if (! array_key_exists(self::MESSAGE_CORRELATION_ID, $headers)) {
            $headers[self::MESSAGE_CORRELATION_ID] = $headers[self::MESSAGE_ID];
        }

        return new static($headers);
    }

    /**
     * @return false|string
     */
    public function __toString()
    {
        return json_encode($this->convertToScalarsIfPossible($this->headers));
    }

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
