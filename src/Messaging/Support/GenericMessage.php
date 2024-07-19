<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Support;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;

use function json_decode;
use function json_encode;

/**
 * Class GenericMessage
 * @package Ecotone\Messaging\Support
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
final class GenericMessage implements Message
{
    /**
     * @var mixed
     */
    private $payload;
    private MessageHeaders $messageHeaders;

    /**
     * GenericMessage constructor.
     *
     * @param mixed          $payload
     * @param MessageHeaders $messageHeaders
     *
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function __construct($payload, MessageHeaders $messageHeaders)
    {
        if ($payload instanceof Message) {
            throw InvalidArgumentException::create("Payload of Generic Message can not be another message for {$payload}");
        }
        if (is_null($payload)) {
            throw InvalidArgumentException::create('Trying to create message with null payload. Message must always contain payload');
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
    public static function create($payload, MessageHeaders $messageHeaders): self
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
    public function getPayload(): mixed
    {
        return $this->payload;
    }

    public function __toString()
    {
        return json_encode([
            'payload' => $this->payload,
            'headers' => json_decode((string)$this->getHeaders(), true),
        ]);
    }
}
