<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\Support\MessageBuilder;
use Throwable;

/**
 * licence Apache-2.0
 */
final class SerializationChannelAdapter implements ChannelInterceptor
{
    public function __construct(private MediaType $targetMediaType, private ConversionService $conversionService)
    {
    }

    public function preSend(Message $message, MessageChannel $messageChannel): ?Message
    {
        return MessageBuilder::fromMessage($message)
                ->setPayload($this->conversionService->convert(
                    $message->getPayload(),
                    Type::createFromVariable($message->getPayload()),
                    $message->getHeaders()->getContentType(),
                    $this->targetMediaType->hasTypeParameter() ? $this->targetMediaType->getTypeParameter() : Type::string(),
                    $this->targetMediaType
                ))
                ->setContentType($this->targetMediaType)
                ->build();
    }

    public function postSend(Message $message, MessageChannel $messageChannel): void
    {
    }

    public function afterSendCompletion(Message $message, MessageChannel $messageChannel, ?Throwable $exception): bool
    {
        return false;
    }

    public function preReceive(MessageChannel $messageChannel): bool
    {
        return true;
    }

    public function postReceive(Message $message, MessageChannel $messageChannel): ?Message
    {
        return $message;
    }

    public function afterReceiveCompletion(?Message $message, MessageChannel $messageChannel, ?Throwable $exception): void
    {
    }
}
