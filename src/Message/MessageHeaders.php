<?php

namespace Messaging\Message;

use Messaging\Clock;
use Messaging\Exception\Message\InvalidMessageHeaderException;
use Messaging\Exception\Message\MessageHeaderDoesNotExistsException;
use Messaging\UuidGenerator;
use Ramsey\Uuid\Uuid;

/**
 * Class MessageHeaders
 * @package Messaging\Message
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MessageHeaders
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
     * The time the message was created. Changes each time a message is mutated.
     */
    const TIMESTAMP = 'timestamp';
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
     * Indicates when a message is expired
     */
    const EXPIRATION_DATE = 'expirationDate';
    /**
     * Message priority; for example within a PriorityChannel
     */
    const PRIORITY = 'priority';
    /**
     * True if a message was detected as a duplicate by an idempotent receiver interceptor
     */
    const DUPLICATE_MESSAGE = 'duplicateMessage';

    /**
     * @var array|string[]
     */
    private $headers;


    /**
     * MessageHeaders constructor.
     * @param array $headers
     */
    private function __construct(array $headers)
    {
        $this->initialize($headers);
    }

    /**
     * @param Clock $clock
     * @return MessageHeaders
     */
    public static function createEmpty(Clock $clock) : self
    {
        return self::createWithCustomHeaders($clock, []);
    }

    /**
     * @param int $timestamp
     * @return MessageHeaders
     */
    public static function createWithTimestamp(int $timestamp) : self
    {
        $headers = [];
        $correlationId = Uuid::uuid4()->toString();

        return self::createMessageHeadersWith($headers, $correlationId, $timestamp);
    }

    /**
     * @param Clock $clock
     * @param array|string[] $headers
     * @return MessageHeaders
     */
    public static function createWithCustomHeaders(Clock $clock, array $headers) : self
    {
        $timestamp = $clock->getCurrentTimestamp();
        $correlationId = Uuid::uuid4()->toString();
        return self::createMessageHeadersWith($headers, $correlationId, $timestamp);
    }

    /**
     * @param int $timestamp
     * @param array|string[] $headers
     * @return MessageHeaders
     */
    public static function createWithCustomHeadersAndTimestamp(int $timestamp, array $headers) : self
    {
        $correlationId = Uuid::uuid4()->toString();
        return self::createMessageHeadersWith($headers, $correlationId, $timestamp);
    }

    /**
     * @param Clock $clock
     * @param array $headers
     * @param MessageHeaders $correlatedMessage
     * @return MessageHeaders
     */
    public static function createWithCorrelated(Clock $clock, array $headers, MessageHeaders $correlatedMessage) : self
    {
        return self::createMessageHeadersWith($headers, $correlatedMessage->get(self::MESSAGE_CORRELATION_ID), $clock->getCurrentTimestamp());
    }

    /**
     * @param Clock $clock
     * @param array $headers
     * @param MessageHeaders $causationMessage
     * @return MessageHeaders
     */
    public static function createWithCausation(Clock $clock, array $headers, MessageHeaders $causationMessage) : self
    {
        $headersWithCausationId = $headers;
        $headersWithCausationId[self::CAUSATION_MESSAGE_ID] = $causationMessage->get(self::MESSAGE_ID);

        return self::createMessageHeadersWith($headersWithCausationId, $causationMessage->get(self::MESSAGE_CORRELATION_ID), $clock->getCurrentTimestamp());
    }

    /**
     * @return array|string[]
     */
    public function headers() : array
    {
        return $this->headers;
    }

    /**
     * @param string $headerName
     * @return bool
     */
    public function containsKey(string $headerName) : bool
    {
        return array_key_exists($headerName, $this->headers);
    }

    /**
     * @param string $value
     * @return bool
     */
    public function containsValue(string $value) : bool
    {
        return in_array($value, $this->headers);
    }

    /**
     * @param string $headerName
     * @return string
     * @throws \Messaging\Exception\MessagingException
     */
    public function get(string $headerName) : string
    {
        if (!$this->containsKey($headerName)) {
            throw MessageHeaderDoesNotExistsException::create("Header with name {$headerName} does not exists");
        }

        return $this->headers[$headerName];
    }

    /**
     * @return int
     */
    public function size() : int
    {
        return count($this->headers());
    }

    /**
     * @param MessageHeaders $messageHeaders
     * @return bool
     */
    public function equals(MessageHeaders $messageHeaders) : bool
    {
        return $this == $messageHeaders;
    }

    /**
     * @param array|string[] $headers
     * @throws \Messaging\Exception\MessagingException
     */
    private function initialize(array $headers) : void
    {
        foreach ($headers as $headerName => $headerValue) {
            if (!$headerName) {
                throw InvalidMessageHeaderException::create("Passed empty header name");
            }
            if (!is_scalar($headerValue)) {
                throw InvalidMessageHeaderException::create("Passed header value {$headerName} is not correct type. It should be scalar");
            }
        }

        $this->headers = $headers;
    }

    /**
     * @param array $headers
     * @param $correlationId
     * @param $timestamp
     * @return MessageHeaders
     */
    private static function createMessageHeadersWith(array $headers, $correlationId, $timestamp): MessageHeaders
    {
        return new self(array_merge($headers, [
            self::MESSAGE_ID => Uuid::uuid4()->toString(),
            self::MESSAGE_CORRELATION_ID => $correlationId,
            self::TIMESTAMP => $timestamp
        ]));
    }
}