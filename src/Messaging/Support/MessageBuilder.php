<?php

namespace Ecotone\Messaging\Support;

use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageChannel;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\MessageHeaderDoesNotExistsException;
use Ecotone\Messaging\MessagingException;
use Symfony\Component\Uid\Uuid;

/**
 * licence Apache-2.0
 */
final class MessageBuilder
{
    private mixed $payload = null;
    private HeaderAccessor $headerAccessor;

    public function __construct(private EcotoneClockInterface $clock)
    {
        $this->headerAccessor = HeaderAccessor::create();
    }

    public function withPayload(mixed $payload): self
    {
        return $this->draft($payload, HeaderAccessor::create());
    }

    public function fromMessage(Message $message): self
    {
        return $this->draft($message->getPayload(), HeaderAccessor::createFrom($message->getHeaders()));
    }

    public function fromParentMessage(Message $message): self
    {
        return $this->fromMessage($message)
            ->setHeader(MessageHeaders::MESSAGE_ID, Uuid::v7()->toRfc4122())
            ->removeHeader(MessageHeaders::TIMESTAMP);
    }

    private function draft(mixed $payload, HeaderAccessor $headerAccessor): self
    {
        Assert::notNull($payload, 'Trying to configure message with null payload');
        if ($payload instanceof Message) {
            throw InvalidArgumentException::create("Payload for building message can not be another message for {$payload}");
        }

        $draft = new self($this->clock);
        $draft->payload = $payload;
        $draft->headerAccessor = $headerAccessor;

        return $draft;
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
        Assert::notNull($this->payload, 'MessageBuilder is the container service; call withPayload(), fromMessage() or fromParentMessage() on it before build().');
        $this->headerAccessor->setHeaderIfAbsent(MessageHeaders::TIMESTAMP, $this->clock->now()->unixTime()->inSeconds());

        return GenericMessage::create(
            $this->payload,
            MessageHeaders::create(
                $this->headerAccessor->headers()
            )
        );
    }
}
