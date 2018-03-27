<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Support;

use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
use SimplyCodedSoftware\IntegrationMessaging\Support\Clock\ServerClock;

/**
 * Class MessageBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Support
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
     * MessageBuilder constructor.
     * @param $payload
     * @param HeaderAccessor $headerAccessor
     */
    private function __construct($payload, HeaderAccessor $headerAccessor)
    {
        $this->payload = $payload;
        $this->headerAccessor = $headerAccessor;

        $this->initialize($payload);
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
     * @param array|string[] $headers
     * @return MessageBuilder
     */
    public function setMultipleHeaders(array $headers) : self
    {
        foreach ($headers as $headerName => $header) {
            $this->headerAccessor->setHeader($headerName, $header);
        }

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
     * @param MessageChannel $replyChannel
     * @return MessageBuilder
     */
    public function setReplyChannel(MessageChannel $replyChannel) : self
    {
        $this->setHeader(MessageHeaders::REPLY_CHANNEL, $replyChannel);

        return $this;
    }

    /**
     * @param MessageChannel $messageChannel
     * @return MessageBuilder
     */
    public function setErrorChannel(MessageChannel $messageChannel) : self
    {
        $this->setHeader(MessageHeaders::ERROR_CHANNEL, $messageChannel);

        return $this;
    }

    /**
     * @param $payload
     * @return MessageBuilder
     */
    public function setPayload($payload) : self
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * @return Message
     */
    public function build() : Message
    {
        $messageHeaders = MessageHeaders::create(
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

    /**
     * @param Message $message
     * @return MessageBuilder
     */
    public static function fromMessage(Message $message) : self
    {
        return new self($message->getPayload(), HeaderAccessor::createFrom($message->getHeaders()));
    }

    /**
     * @param $payload
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function initialize($payload) : void
    {
        Assert::notNull($payload, "Message payload can't be empty");

        $this->headerAccessor->setHeaderIfAbsent(MessageHeaders::ERROR_CHANNEL, "errorChannel");
    }
}