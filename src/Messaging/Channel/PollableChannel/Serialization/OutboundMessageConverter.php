<?php

namespace Ecotone\Messaging\Channel\PollableChannel\Serialization;

use DateTimeInterface;
use Ecotone\Messaging\Conversion\ConversionException;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Scheduling\DatePoint;
use Ecotone\Messaging\Scheduling\Duration;
use Ecotone\Messaging\Scheduling\TimeSpan;

/**
 * licence Apache-2.0
 */
class OutboundMessageConverter
{
    public function __construct(
        private HeaderMapper $headerMapper,
        private ?MediaType $defaultConversionMediaType = null,
        private ?int $defaultDeliveryDelay = null,
        private ?int $defaultTimeToLive = null,
        private ?int $defaultPriority = null,
        private array $staticHeadersToAdd = []
    ) {
    }

    public function prepare(Message $messageToConvert, ConversionService $conversionService): OutboundMessage
    {
        $messagePayload = $messageToConvert->getPayload();

        $applicationHeaders = $messageToConvert->getHeaders()->headers() ?? [];
        $applicationHeaders = MessageHeaders::unsetAggregateKeys($applicationHeaders);
        $applicationHeaders = MessageHeaders::unsetEnqueueMetadata($applicationHeaders);

        $applicationHeaders                             = $this->headerMapper->mapFromMessageHeaders($applicationHeaders, $conversionService);
        $applicationHeaders[MessageHeaders::MESSAGE_ID] = $messageToConvert->getHeaders()->getMessageId();
        $applicationHeaders[MessageHeaders::TIMESTAMP]  = $messageToConvert->getHeaders()->getTimestamp();

        $sourceMediaType             = $messageToConvert->getHeaders()->hasContentType() ? $messageToConvert->getHeaders()->getContentType() : null;
        if (! is_string($messagePayload)) {
            if (Type::createFromVariable($messagePayload)->isScalar()) {
                $sourceMediaType = MediaType::createApplicationXPHP();
            }
            if (! $sourceMediaType) {
                throw new ConversionException("Can't send outside of application. Payload has incorrect type, that can't be converted: " . Type::createFromVariable($messagePayload)->toString());
            }

            $sourceType      = $sourceMediaType->hasTypeParameter() ? $sourceMediaType->getTypeParameter() : Type::createFromVariable($messagePayload);
            $sourceMediaType = $sourceMediaType->withoutTypeParameter();
            $targetConversionMediaType = $this->defaultConversionMediaType ?: MediaType::createApplicationXPHPSerialized();
            $targetType = Type::string();
            if ($targetConversionMediaType->hasTypeParameter()) {
                $targetType = $targetConversionMediaType->getTypeParameter();
            } elseif ($targetConversionMediaType->isCompatibleWith(MediaType::createApplicationXPHP())) {
                $targetType = Type::anything();
            }

            if ($this->doesRequireConversion($sourceMediaType, $sourceType, $targetConversionMediaType, $targetType)) {
                if ($conversionService->canConvert(
                    $sourceType,
                    $sourceMediaType,
                    $targetType,
                    $targetConversionMediaType
                )) {
                    if (! isset($applicationHeaders[MessageHeaders::TYPE_ID])) {
                        $applicationHeaders[MessageHeaders::TYPE_ID] = Type::createFromVariable($messagePayload)->toString();
                    }
                    $messagePayload = $conversionService->convert(
                        $messagePayload,
                        $sourceType,
                        $sourceMediaType,
                        $targetType,
                        $targetConversionMediaType
                    );

                    $sourceMediaType = $targetConversionMediaType;
                } elseif ($sourceType->isString()) {
                    if (is_null($sourceMediaType)) {
                        $sourceMediaType = MediaType::createTextPlain();
                    }
                } else {
                    throw new ConversionException(
                        "Can't send message to external channel. Payload has incorrect non-convertable type or converter is missing for:
                 From {$sourceMediaType}:{$sourceType} to {$targetConversionMediaType}:{$targetType}"
                    );
                }
            }
        }

        if ($messageToConvert->getHeaders()->containsKey(MessageHeaders::ROUTING_SLIP)) {
            $applicationHeaders[MessageHeaders::ROUTING_SLIP] = $messageToConvert->getHeaders()->get(MessageHeaders::ROUTING_SLIP);
        }
        $applicationHeaders[MessageHeaders::CONTENT_TYPE] = $sourceMediaType?->toString();

        $deliveryDelay = $messageToConvert->getHeaders()->containsKey(MessageHeaders::DELIVERY_DELAY) ? $messageToConvert->getHeaders()->get(MessageHeaders::DELIVERY_DELAY) : $this->defaultDeliveryDelay;

        if ($deliveryDelay instanceof DateTimeInterface) {
            $deliveryDelay = DatePoint::createFromInterface($deliveryDelay)->durationSince(DatePoint::createFromTimestamp($messageToConvert->getHeaders()->getTimestamp()));
        }

        if ($deliveryDelay instanceof Duration) {
            $deliveryDelay = $deliveryDelay->inMilliseconds();
        }

        if ($deliveryDelay instanceof TimeSpan) {
            $deliveryDelay = $deliveryDelay->toMilliseconds();
        }

        if ($deliveryDelay && $deliveryDelay < 0) {
            $deliveryDelay = null;
        }

        return new OutboundMessage(
            $messagePayload,
            array_merge($applicationHeaders, $this->staticHeadersToAdd),
            $applicationHeaders[MessageHeaders::CONTENT_TYPE],
            $deliveryDelay,
            $messageToConvert->getHeaders()->containsKey(MessageHeaders::TIME_TO_LIVE) ? $messageToConvert->getHeaders()->get(MessageHeaders::TIME_TO_LIVE) : $this->defaultTimeToLive,
            $messageToConvert->getHeaders()->containsKey(MessageHeaders::PRIORITY) ? $messageToConvert->getHeaders()->get(MessageHeaders::PRIORITY) : $this->defaultPriority,
        );
    }

    private function doesRequireConversion(
        MediaType $sourceMediaType,
        Type $sourceType,
        MediaType $targetConversionMediaType,
        Type $targetType
    ): bool {
        return ! ($sourceMediaType->isCompatibleWith($targetConversionMediaType) && $sourceType->isCompatibleWith($targetType));
    }
}
