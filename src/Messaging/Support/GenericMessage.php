<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Support;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHeaders;

/**
 * Class GenericMessage
 * @package SimplyCodedSoftware\Messaging\Support
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
     *
     * @param mixed          $payload
     * @param MessageHeaders $messageHeaders
     *
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct($payload, MessageHeaders $messageHeaders)
    {
        if (is_null($payload)) {
            throw InvalidArgumentException::create("Trying to create message with null payload. Message must always contain payload");
        }

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
        return \json_encode([
            "payload" => $this->payload,
            "headers" => \json_decode((string)$this->getHeaders(), true)
        ]);
    }
}