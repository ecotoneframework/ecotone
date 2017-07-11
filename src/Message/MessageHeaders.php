<?php

namespace Messaging\Message;

use Messaging\Clock;
use Messaging\Exception\Message\InvalidMessageHeaderException;
use Messaging\UuidGenerator;

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
    const MESSAGE_PARENT_ID = 'parentId';
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
     * @var array
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
     * @param UuidGenerator $uuidGenerator
     * @param Clock $clock
     * @return MessageHeaders
     */
    public static function createEmpty(UuidGenerator $uuidGenerator, Clock $clock) : self
    {
        return new self([
            self::MESSAGE_ID => $uuidGenerator->generateUuid()->toString(),
            self::MESSAGE_CORRELATION_ID => $uuidGenerator->generateUuid()->toString(),
            self::TIMESTAMP => $clock->getCurrentTimestamp()
        ]);
    }

    /**
     * @param array|string[] $headers
     * @return MessageHeaders
     */
    public static function createWith(array $headers) : self
    {
        return new self($headers);
    }

    /**
     * @return array|string[]
     */
    public function headers() : array
    {
        return $this->headers;
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
}