<?php

namespace Ecotone\Messaging\Channel\PollableChannel\Serialization;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;

class OutboundMessageConverter
{
    private HeaderMapper $headerMapper;
    private ?MediaType $defaultConversionMediaType;
    private ?int $defaultDeliveryDelay;
    private ?int $defaultTimeToLive;
    private ?int $defaultPriority;
    private array $staticHeadersToAdd;

    public function __construct(HeaderMapper $headerMapper, ?MediaType $defaultConversionMediaType, ?int $defaultDeliveryDelay, ?int $defaultTimeToLive, ?int $defaultPriority, array $staticHeadersToAdd)
    {
        $this->headerMapper               = $headerMapper;
        $this->defaultConversionMediaType = $defaultConversionMediaType;
        $this->defaultDeliveryDelay       = $defaultDeliveryDelay;
        $this->defaultTimeToLive          = $defaultTimeToLive;
        $this->defaultPriority            = $defaultPriority;
        $this->staticHeadersToAdd = $staticHeadersToAdd;
    }

    public function prepare(Message $messageToConvert, ConversionService $conversionService): OutboundMessage
    {
        $messagePayload = $messageToConvert->getPayload();

        $applicationHeaders = $messageToConvert->getHeaders()->headers() ?? [];
        $applicationHeaders = MessageHeaders::unsetEnqueueMetadata($applicationHeaders);

        $applicationHeaders                             = $this->headerMapper->mapFromMessageHeaders($applicationHeaders, $conversionService);
        $applicationHeaders[MessageHeaders::MESSAGE_ID] = $messageToConvert->getHeaders()->getMessageId();
        $applicationHeaders[MessageHeaders::TIMESTAMP]  = $messageToConvert->getHeaders()->getTimestamp();

        $mediaType             = $messageToConvert->getHeaders()->hasContentType() ? $messageToConvert->getHeaders()->getContentType() : null;
        if (! is_string($messagePayload)) {
            if (! $messageToConvert->getHeaders()->hasContentType()) {
                throw new InvalidArgumentException("Can't send outside of application. Payload has incorrect type, that can't be converted: " . TypeDescriptor::createFromVariable($messagePayload)->toString());
            }

            $sourceType      = $messageToConvert->getHeaders()->getContentType()->hasTypeParameter() ? $messageToConvert->getHeaders()->getContentType()->getTypeParameter() : TypeDescriptor::createFromVariable($messagePayload);
            $sourceMediaType = $messageToConvert->getHeaders()->getContentType();
            $targetType      = TypeDescriptor::createStringType();

            $defaultConversionMediaType = $this->defaultConversionMediaType ?: MediaType::createApplicationXPHPSerialized();
            if ($conversionService->canConvert(
                $sourceType,
                $sourceMediaType,
                $targetType,
                $defaultConversionMediaType
            )) {
                $applicationHeaders[MessageHeaders::TYPE_ID] = TypeDescriptor::createFromVariable($messagePayload)->toString();

                $mediaType             = $defaultConversionMediaType;
                $messagePayload = $conversionService->convert(
                    $messagePayload,
                    $sourceType,
                    $messageToConvert->getHeaders()->getContentType(),
                    $targetType,
                    $mediaType
                );
            } elseif ($sourceType->isString()) {
                if (is_null($mediaType)) {
                    $mediaType = MediaType::createTextPlain();
                }
            } else {
                throw new InvalidArgumentException(
                    "Can't send message to external channel. Payload has incorrect non-convertable type or converter is missing for:
                 From {$sourceMediaType}:{$sourceType} to {$defaultConversionMediaType}:{$targetType}"
                );
            }
        }

        if ($messageToConvert->getHeaders()->containsKey(MessageHeaders::ROUTING_SLIP)) {
            $applicationHeaders[MessageHeaders::ROUTING_SLIP] = $messageToConvert->getHeaders()->get(MessageHeaders::ROUTING_SLIP);
        }

        return new OutboundMessage(
            $messagePayload,
            array_merge($applicationHeaders, $this->staticHeadersToAdd),
            $mediaType ? $mediaType->toString() : null,
            $messageToConvert->getHeaders()->containsKey(MessageHeaders::DELIVERY_DELAY) ? $messageToConvert->getHeaders()->get(MessageHeaders::DELIVERY_DELAY) : $this->defaultDeliveryDelay,
            $messageToConvert->getHeaders()->containsKey(MessageHeaders::TIME_TO_LIVE) ? $messageToConvert->getHeaders()->get(MessageHeaders::TIME_TO_LIVE) : $this->defaultTimeToLive,
            $messageToConvert->getHeaders()->containsKey(MessageHeaders::PRIORITY) ? $messageToConvert->getHeaders()->get(MessageHeaders::PRIORITY) : $this->defaultPriority,
        );
    }
}
