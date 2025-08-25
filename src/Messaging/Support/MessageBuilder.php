<?php

namespace Ecotone\Messaging\Support;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaderDoesNotExistsException;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ramsey\Uuid\Uuid;

/**
 * Class MessageBuilder
 * @package Ecotone\Messaging\Support
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
final class MessageBuilder
{
    private mixed $payload;
    private HeaderAccessor $headerAccessor;

    private function __construct(mixed $payload, HeaderAccessor $headerAccessor)
    {
        Assert::notNull($payload, 'Trying to configure message with null payload');
        if ($payload instanceof Message) {
            throw InvalidArgumentException::create("Payload for building message can not be another message for {$payload}");
        }
        $this->payload = $payload;
        $this->headerAccessor = $headerAccessor;

        $this->initialize($payload);
    }

    /**
     * @param $payload
     * @throws MessagingException
     */
    private function initialize($payload): void
    {
        Assert::notNull($payload, "Message payload can't be empty");
    }

    /**
     * @param mixed $payload
     * @return MessageBuilder
     */
    public static function withPayload($payload): self
    {
        return new self($payload, HeaderAccessor::create());
    }

    public static function fromMessage(Message $message): self
    {
        return new self($message->getPayload(), HeaderAccessor::createFrom($message->getHeaders()));
    }

    public static function fromParentMessage(Message $message): self
    {
        return (new self($message->getPayload(), HeaderAccessor::createFrom($message->getHeaders())))
            ->setHeader(MessageHeaders::MESSAGE_ID, Uuid::uuid4()->toString())
            ->removeHeader(MessageHeaders::TIMESTAMP);
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param $payload
     * @return MessageBuilder
     */
    public function setPayload($payload): self
    {
        Assert::notNull($payload, 'Trying to configure message with null payload');
        if ($payload instanceof Message) {
            throw InvalidArgumentException::create("Payload for building message can not be another message for {$payload}");
        }

        $this->payload = $payload;

        return $this;
    }

    /**
     * @param MediaType $mediaType
     * @return MessageBuilder
     */
    public function setContentType(MediaType $mediaType): self
    {
        $this->setHeader(MessageHeaders::CONTENT_TYPE, $mediaType->toString());

        return $this;
    }

    /**
     * @param string $headerName
     * @param $headerValue
     * @return MessageBuilder
     */
    public function setHeader(string $headerName, $headerValue): self
    {
        $this->headerAccessor->setHeader($headerName, $headerValue);

        return $this;
    }

    /**
     * @param string[] $routingSlip
     */
    public function setRoutingSlip(array $routingSlip): self
    {
        if (! $routingSlip) {
            return $this->removeHeader(MessageHeaders::ROUTING_SLIP);
        }

        $this->setHeader(MessageHeaders::ROUTING_SLIP, implode(',', $routingSlip));

        return $this;
    }

    public function prependRoutingSlip(array $routingSlip): self
    {
        if (! $routingSlip) {
            return $this;
        }

        $this->setHeader(MessageHeaders::ROUTING_SLIP, implode(',', array_merge($routingSlip, $this->getRoutingSlip())));

        return $this;
    }

    /**
     * @return string[]
     */
    public function getRoutingSlip(): array
    {
        if (! $this->headerAccessor->hasHeader(MessageHeaders::ROUTING_SLIP)) {
            return [];
        }

        return explode(',', $this->headerAccessor->getHeader(MessageHeaders::ROUTING_SLIP));
    }

    /**
     * @param MediaType $mediaType
     * @return MessageBuilder
     */
    public function setContentTypeIfAbsent(MediaType $mediaType): self
    {
        $this->setHeaderIfAbsent(MessageHeaders::CONTENT_TYPE, $mediaType->toString());

        return $this;
    }

    /**
     * @param string $headerName
     * @param $headerValue
     * @return MessageBuilder
     */
    public function setHeaderIfAbsent(string $headerName, $headerValue): self
    {
        $this->headerAccessor->setHeaderIfAbsent($headerName, $headerValue);

        return $this;
    }

    /**
     * @param array|string[] $headers
     * @return MessageBuilder
     */
    public function setMultipleHeaders(array $headers): self
    {
        foreach ($headers as $headerName => $header) {
            $this->headerAccessor->setHeader($headerName, $header);
        }

        return $this;
    }

    public function removeHeaders(array $headerNames): self
    {
        foreach ($headerNames as $headerName) {
            $this->headerAccessor->removeHeader($headerName);
        }

        return $this;
    }

    /**
     * @param string $headerName
     * @return MessageBuilder
     */
    public function removeHeader(string $headerName): self
    {
        $this->headerAccessor->removeHeader($headerName);

        return $this;
    }

    /**
     * @param MessageChannel $replyChannel
     * @return MessageBuilder
     */
    public function setReplyChannel(MessageChannel $replyChannel): self
    {
        $this->setHeader(MessageHeaders::REPLY_CHANNEL, $replyChannel);

        return $this;
    }

    /**
     * @param MessageChannel $messageChannel
     * @return MessageBuilder
     */
    public function setErrorChannel(MessageChannel $messageChannel): self
    {
        $this->setHeader(MessageHeaders::ERROR_CHANNEL, $messageChannel);

        return $this;
    }

    public function containsKey(string $headerName): bool
    {
        return array_key_exists($headerName, $this->getCurrentHeaders());
    }

    /**
     * @return array
     */
    public function getCurrentHeaders(): array
    {
        return $this->headerAccessor->headers();
    }

    /**
     * @param string $name
     * @return mixed
     * @throws MessagingException
     */
    public function getHeaderWithName(string $name)
    {
        if (! array_key_exists($name, $this->getCurrentHeaders())) {
            throw MessageHeaderDoesNotExistsException::create("Tries to retrieve not existing header with name {$name}");
        }

        return $this->getCurrentHeaders()[$name];
    }

    public function build(): GenericMessage
    {
        return GenericMessage::create(
            $this->payload,
            MessageHeaders::create(
                $this->headerAccessor->headers()
            )
        );
    }
}
