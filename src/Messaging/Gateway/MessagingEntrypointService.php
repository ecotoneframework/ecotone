<?php

namespace Ecotone\Messaging\Gateway;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagatorInterceptor;
use Ramsey\Uuid\Uuid;

/**
 * licence Apache-2.0
 */
class MessagingEntrypointService
{
    public const ENTRYPOINT = 'ecotone.messaging.entrypoint';
    private ?MessageChannel $entrypointChannel = null;

    public function __construct(
        private ChannelResolver $channelResolver,
        private MessageHeadersPropagatorInterceptor $messageHeadersPropagator,
    ) {
    }

    public function send(mixed $payload, string $targetChannel): mixed
    {
        return $this->sendWithHeaders($payload, [], $targetChannel);
    }

    public function sendWithHeaders(mixed $payload, array $headers, string $targetChannel, ?string $routingSlip = null): mixed
    {
        $replyChannel = QueueChannel::create('messaging-entrypoint-reply');

        $messageBuilder = $this->createMessageBuilder($payload, $headers, $targetChannel)
            ->setReplyChannel($replyChannel);

        if ($routingSlip !== null) {
            $messageBuilder->setHeader(MessageHeaders::ROUTING_SLIP, $routingSlip);
        }

        $this->getEntrypointChannel()->send($messageBuilder->build());

        return $replyChannel->receive()?->getPayload();
    }

    public function sendWithHeadersWithMessageReply(mixed $payload, array $headers, string $targetChannel, ?string $routingSlip = null): ?Message
    {
        $replyChannel = QueueChannel::create('messaging-entrypoint-reply');

        $messageBuilder = $this->createMessageBuilder($payload, $headers, $targetChannel)
            ->setReplyChannel($replyChannel);

        if ($routingSlip !== null) {
            $messageBuilder->setHeader(MessageHeaders::ROUTING_SLIP, $routingSlip);
        }

        $this->getEntrypointChannel()->send($messageBuilder->build());

        return $replyChannel->receive();
    }

    public function sendWithHeadersPropagation(mixed $payload, array $headers, string $targetChannel, ?string $routingSlip = null): mixed
    {
        $headers = $this->messageHeadersPropagator->propagateHeaders($headers);

        $replyChannel = QueueChannel::create('messaging-entrypoint-reply');

        $messageBuilder = $this->createMessageBuilder($payload, $headers, $targetChannel)
            ->setReplyChannel($replyChannel);

        if ($routingSlip !== null) {
            $messageBuilder->setHeader(MessageHeaders::ROUTING_SLIP, $routingSlip);
        }

        $message = $messageBuilder->build();

        return $this->messageHeadersPropagator->storeHeaders(
            function () use ($message, $replyChannel) {
                $this->getEntrypointChannel()->send($message);

                return $replyChannel->receive()?->getPayload();
            },
            $message
        );
    }

    public function sendWithHeadersPropagationAndMessageReply(mixed $payload, array $headers, string $targetChannel, ?string $routingSlip = null): ?Message
    {
        $headers = $this->messageHeadersPropagator->propagateHeaders($headers);

        $replyChannel = QueueChannel::create('messaging-entrypoint-reply');

        $messageBuilder = $this->createMessageBuilder($payload, $headers, $targetChannel)
            ->setReplyChannel($replyChannel);

        if ($routingSlip !== null) {
            $messageBuilder->setHeader(MessageHeaders::ROUTING_SLIP, $routingSlip);
        }

        $message = $messageBuilder->build();

        return $this->messageHeadersPropagator->storeHeaders(
            function () use ($message, $replyChannel) {
                $this->getEntrypointChannel()->send($message);

                return $replyChannel->receive();
            },
            $message
        );
    }

    public function sendMessage(Message $message): mixed
    {
        $replyChannel = QueueChannel::create('messaging-entrypoint-reply');
        $message = MessageBuilder::fromMessage($message)
            ->setReplyChannel($replyChannel)
            ->build();

        $this->getEntrypointChannel()->send($message);

        return $replyChannel->receive()?->getPayload();
    }

    private function getEntrypointChannel(): MessageChannel
    {
        return $this->entrypointChannel ??= $this->channelResolver->resolve(self::ENTRYPOINT);
    }

    private function createMessageBuilder(mixed $payload, array $headers, string $targetChannel): MessageBuilder
    {
        $messageId = Uuid::uuid4()->toString();

        return MessageBuilder::withPayload($payload)
            ->setHeader(MessageHeaders::MESSAGE_ID, $messageId)
            ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $messageId)
            ->setContentTypeIfAbsent(MediaType::createApplicationXPHPWithTypeParameter(Type::createFromVariable($payload)->getTypeHint()))
            ->setMultipleHeaders($headers)
            ->setHeader(self::ENTRYPOINT, $targetChannel);
    }
}
