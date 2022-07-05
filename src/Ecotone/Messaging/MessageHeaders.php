<?php

namespace Ecotone\Messaging;

use Ramsey\Uuid\Uuid;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * Class MessageHeaders
 * @package Ecotone\Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageHeaders
{
    /**
     * An identifier for this message instance. Changes each time a message is mutated.
     */
    const MESSAGE_ID = 'id';
    /**
     * Used to correlate two or more messages.
     */
    const MESSAGE_CORRELATION_ID = 'correlationId';
    /**
     * Used to point parent message
     */
    const CAUSATION_MESSAGE_ID = 'parentId';
    /**
     * content-type values are parsed as media types, e.g., application/json or text/plain;charset=UTF-8
     */
    const CONTENT_TYPE = 'contentType';
    /**
     * Type of object under payload
     */
    const TYPE_ID = "__TypeId__";
    /**
     * Encoding of payload body
     */
    const CONTENT_ENCODING = 'contentEncoding';
    /**
     * The time the message was created. Changes each time a message is mutated.
     */
    const TIMESTAMP = 'timestamp';
    /**
     * List of channels comma separated, where to is next endpoint
     */
    const ROUTING_SLIP = "routingSlip";
    /**
     * A channel to which errors will be sent. It must represent a name from registry of a class implementing MessageChannel
     */
    const REPLY_CHANNEL = 'replyChannel';
    /**
     * A channel to which errors will be sent. It must represent a name from registry of a class implementing MessageChannel
     */
    const ERROR_CHANNEL = 'errorChannel';
    /**
     * Usually a sequence number with a group of messages with a SEQUENCE_SIZE
     */
    const SEQUENCE_NUMBER = 'sequenceNumber';
    /**
     * The number of messages within a group of correlated messages.
     */
    const SEQUENCE_SIZE = 'sequenceSize';
    /**
     * Message priority; for example within a PriorityChannel
     */
    const PRIORITY = 'priority';
    /**
     * True if a message was detected as a duplicate by an idempotent receiver interceptor
     */
    const DUPLICATE_MESSAGE = 'duplicateMessage';
    /**
     * Time to live of message in milliseconds
     */
    const TIME_TO_LIVE = "timeToLive";
    /**
     * Delivery delay in milliseconds
     */
    const DELIVERY_DELAY = "deliveryDelay";
    /**
     * Informs under which key acknowledge callback is stored for this consumer message
     */
    const CONSUMER_ACK_HEADER_LOCATION = "consumerAcknowledgeCallbackHeader";
    /**
     * Consumer which started flow
     */
    const CONSUMER_ENDPOINT_ID = "consumerEndpointId";
    /**
     * Consumed channel name
     */
    const POLLED_CHANNEL_NAME = "polledChannelName";
    /**
     * Expected content type of reply
     */
    const REPLY_CONTENT_TYPE = "replyContentType";

    private ?array $headers;


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
    final public static function createEmpty() : self
    {
        return static::createMessageHeadersWith([]);
    }

    /**
     * @param array|string[] $headers
     * @return MessageHeaders|static
     */
    final public static function create(array $headers) : self
    {
        return static::createMessageHeadersWith($headers);
    }

    final public function headers() : ?array
    {
        return $this->headers;
    }

    public static function getFrameworksHeaderNames() : array
    {
        return [
            self::MESSAGE_ID,
            self::MESSAGE_CORRELATION_ID,
            self::CAUSATION_MESSAGE_ID,
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
            self::CONSUMER_ENDPOINT_ID
        ];
    }

    /**
     * @param string $headerRegex e.g. ecotone-domain-*
     *
     * @return array
     */
    final public function findByRegex(string $headerRegex) : array
    {
        $foundHeaders = [];
        $headerRegex = str_replace(".", "\.", $headerRegex);
        $headerRegex = str_replace("*", ".*", $headerRegex);
        $headerRegex = "#" . $headerRegex . "#";

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
    final public function containsKey(string $headerName) : bool
    {
        return array_key_exists($headerName, $this->headers);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    final public function containsValue($value) : bool
    {
        return in_array($value, $this->headers);
    }

    /**
     * @param string $headerName
     * @return mixed
     * @throws \Ecotone\Messaging\MessagingException
     */
    final public function get(string $headerName)
    {
        if (!$this->containsKey($headerName)) {
            throw MessageHeaderDoesNotExistsException::create("Header with name {$headerName} does not exists");
        }

        return $this->headers[$headerName];
    }

    /**
     * @return int
     */
    final public function size() : int
    {
        return count($this->headers());
    }

    /**
     * @param MessageHeaders $messageHeaders
     * @return bool
     */
    final public function equals(MessageHeaders $messageHeaders) : bool
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
    final public function hasMessageId(string $messageId) : bool
    {
        return $this->get(self::MESSAGE_ID) === $messageId;
    }

    /**
     * @param string $headerName
     * @param $headerValue
     */
    final protected function changeHeader(string $headerName, $headerValue) : void
    {
        $this->headers[$headerName] = $headerValue;
    }

    /**
     * @return bool
     */
    public function hasContentType() : bool
    {
        return $this->containsKey(self::CONTENT_TYPE);
    }

    /**
     * @return string
     * @throws MessagingException
     */
    public function getMessageId() : string
    {
        return $this->get(MessageHeaders::MESSAGE_ID);
    }

    /**
     * @return int
     * @throws MessagingException
     */
    public function getTimestamp() : int
    {
        return $this->get(MessageHeaders::TIMESTAMP);
    }

    /**
     * @return MediaType
     * @throws MessagingException
     * @throws Support\InvalidArgumentException
     */
    public function getContentType() : MediaType
    {
        return MediaType::parseMediaType($this->get(self::CONTENT_TYPE));
    }

    /**
     * @param array|string[] $headers
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function initialize(array $headers) : void
    {
        foreach ($headers as $headerName => $headerValue) {
            if (is_null($headerName) || $headerName === '') {
                throw InvalidMessageHeaderException::create("Passed empty header name");
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
        /** @phpstan-ignore-next-line */
        return new static(array_merge(
            [
                self::MESSAGE_ID => Uuid::uuid4()->toString(),
                self::TIMESTAMP => (int)round(microtime(true))
            ],
            $headers
        ));
    }

    /**
     * @return false|string
     * @throws Handler\TypeDefinitionException
     * @throws MessagingException
     */
    public function __toString()
    {
        return \json_encode($this->convertToScalarsIfPossible($this->headers));
    }

    /**
     * @param iterable $dataToConvert
     * @return array
     * @throws Handler\TypeDefinitionException
     * @throws MessagingException
     */
    private function convertToScalarsIfPossible(iterable $dataToConvert) : array
    {
        $data = [];

        foreach ($dataToConvert as $headerName => $header) {
            if (TypeDescriptor::createFromVariable($header)->isScalar()) {
                $data[$headerName] = $header;
                continue;
            }

            if (is_iterable($header)) {
                $data[$headerName] = $this->convertToScalarsIfPossible($header);
            }else if (is_object($header) && method_exists($header, "__toString")) {
                $data[$headerName] = (string)$header;
            }
        }

        return $data;
    }
}