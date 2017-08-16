<?php

namespace Messaging\Support;

use Messaging\Message;
use Messaging\MessageHeaders;
use Messaging\Support\Clock\ServerClock;

/**
 * Class MessageBuilder
 * @package Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MessageBuilder
{
    /**
     * @var  mixed
     */
    private $payload;
    /**
     * @var HeaderAccessor
     */
    private $headerAccessor;
    /**
     * @var Clock
     */
    private $clock;
    /**
     * @var bool
     */
    private $isMutable;

    /**
     * MessageBuilder constructor.
     * @param $payload
     * @param HeaderAccessor $headerAccessor
     * @param bool $isMutable
     */
    private function __construct($payload, HeaderAccessor $headerAccessor, bool $isMutable)
    {
        $this->payload = $payload;
        $this->headerAccessor = $headerAccessor;
        $this->isMutable = $isMutable;
        $this->initialize();
    }

    /**
     * @param string $headerName
     * @param $headerValue
     * @return MessageBuilder
     */
    public function setHeader(string $headerName, $headerValue) : self
    {
        $this->headerAccessor->setHeader($headerName, $headerValue);

        return $this;
    }

    /**
     * @param string $headerName
     * @return MessageBuilder
     */
    public function removeHeader(string $headerName) : self
    {
        $this->headerAccessor->removeHeader($headerName);

        return $this;
    }

    /**
     * @param string $headerName
     * @param $headerValue
     * @return MessageBuilder
     */
    public function setHeaderIfAbsent(string $headerName, $headerValue) : self
    {
        $this->headerAccessor->setHeaderIfAbsent($headerName, $headerValue);

        return $this;
    }

    /**
     * @param Clock $clock
     * @return MessageBuilder
     */
    public function setClock(Clock $clock) : self
    {
        $this->clock = $clock;

        return $this;
    }

    /**
     * @param string $channelName
     * @return MessageBuilder
     */
    public function setReplyChannelName(string $channelName) : self
    {
        $this->setHeader(MessageHeaders::REPLY_CHANNEL, $channelName);

        return $this;
    }

    /**
     * @param string $channelName
     * @return MessageBuilder
     */
    public function setErrorChannelName(string $channelName) : self
    {
        $this->setHeader(MessageHeaders::ERROR_CHANNEL, $channelName);

        return $this;
    }

    /**
     * @return Message
     */
    public function build() : Message
    {
        $messageHeaders = MutableMessageHeaders::createWithHeaders(
            $this->clock->getCurrentTimestamp(),
            $this->headerAccessor->headers()
        );

        if ($this->areHeadersMutable() && $this->headerAccessor->hasHeader(MessageHeaders::MESSAGE_CORRELATION_ID)) {
            $messageHeaders->withCorrelationMessage(
                $this->headerAccessor->getHeader(MessageHeaders::MESSAGE_CORRELATION_ID)
            );
        }

        if ($this->areHeadersMutable() && $this->headerAccessor->hasHeader(MessageHeaders::CAUSATION_MESSAGE_ID)) {
            $messageHeaders->withCausationMessage(
                $this->headerAccessor->getHeader(MessageHeaders::MESSAGE_CORRELATION_ID),
                $this->headerAccessor->getHeader(MessageHeaders::CAUSATION_MESSAGE_ID)
            );
        }

        return GenericMessage::create(
            $this->payload,
            $messageHeaders
        );
    }

    /**
     * @param mixed $payload
     * @return MessageBuilder
     */
    public static function withPayload($payload) : self
    {
        return new self($payload, HeaderAccessor::create(), false);
    }

    /**
     * @param Message $message
     * @return MessageBuilder
     */
    public static function fromCorrelatedMessage(Message $message) : self
    {
        $headerAccessor = HeaderAccessor::create();
        $headerAccessor->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $message->getHeaders()->get(MessageHeaders::MESSAGE_CORRELATION_ID));

        return new self($message->getPayload(), $headerAccessor, true);
    }

    /**
     * @param Message $message
     * @return MessageBuilder
     */
    public static function fromCausationMessage(Message $message) : self
    {
        $headerAccessor = HeaderAccessor::create();
        $headerAccessor->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $message->getHeaders()->get(MessageHeaders::MESSAGE_CORRELATION_ID));
        $headerAccessor->setHeader(MessageHeaders::CAUSATION_MESSAGE_ID, $message->getHeaders()->get(MessageHeaders::MESSAGE_ID));

        return new self($message->getPayload(), $headerAccessor, true);
    }

    /**
     * @return bool
     */
    private function areHeadersMutable() : bool
    {
        return $this->isMutable;
    }

    private function initialize() : void
    {
        $this->clock = ServerClock::create();
    }
}