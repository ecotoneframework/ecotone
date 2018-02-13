<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Support;

use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;

/**
 * Class GenericMessage
 * @package SimplyCodedSoftware\IntegrationMessaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GenericMessage implements Message
{
    /**
     * @var mixed
     */
    private $payload;
    /**
     * @var MessageHeaders
     */
    private $messageHeaders;

    /**
     * GenericMessage constructor.
     * @param mixed $payload
     * @param MessageHeaders $messageHeaders
     */
    private function __construct($payload, MessageHeaders $messageHeaders)
    {
        $this->payload = $payload;
        $this->messageHeaders = $messageHeaders;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): MessageHeaders
    {
        return $this->messageHeaders;
    }

    /**
     * @param mixed $payload
     * @param MessageHeaders $messageHeaders
     * @return GenericMessage|static
     */
    public static function create($payload, MessageHeaders $messageHeaders) : self
    {
        return new static($payload, $messageHeaders);
    }

    /**
     * @param mixed $payload
     * @param array|string[]|object[]|int[] $headers
     * @return Message
     */
    public static function createWithArrayHeaders($payload, array $headers): Message
    {
        return new static($payload, MessageHeaders::create($headers));
    }

    /**
     * @param mixed $payload
     * @return Message
     */
    public static function createWithEmptyHeaders($payload): Message
    {
        return new static($payload, MessageHeaders::createEmpty());
    }

    /**
     * @inheritDoc
     */
    public function getPayload()
    {
        return $this->payload;
    }

    public function __toString()
    {
        return "Message with id " . (string)$this->getHeaders()->get(MessageHeaders::MESSAGE_ID);
    }
}