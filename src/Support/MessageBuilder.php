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
     * MessageBuilder constructor.
     * @param $payload
     * @param HeaderAccessor $headerAccessor
     */
    private function __construct($payload, HeaderAccessor $headerAccessor)
    {
        $this->payload = $payload;
        $this->headerAccessor = $headerAccessor;
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
        $messageHeaders = MessageHeaders::create(
            $this->clock->getCurrentTimestamp(),
            $this->headerAccessor->headers()
        );

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
        return new self($payload, HeaderAccessor::create());
    }

    private function initialize() : void
    {
        $this->clock = ServerClock::create();
    }
}