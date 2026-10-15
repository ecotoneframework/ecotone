<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\Serialization;

use Ecotone\Api\Messaging\BatchMessage;
use Ecotone\Api\Messaging\ErrorMessage;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageChannel;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Channel\AbstractChannelInterceptor;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
final class OutboundSerializationChannelInterceptor extends AbstractChannelInterceptor
{
    public function __construct(
        private OutboundMessageConverter $outboundMessageConverter,
        private ConversionService $conversionService
    ) {
    }

    /**
     * @inheritDoc
     */
    public function preSend(Message $message, MessageChannel $messageChannel): ?Message
    {
        if ($message instanceof ErrorMessage) {
            return $message;
        }

        if ($message->getPayload() instanceof BatchMessage) {
            return $message;
        }

        $outboundMessage = $this->outboundMessageConverter->prepare($message, $this->conversionService);
        $preparedMessage = MessageBuilder::withPayload($outboundMessage->getPayload())
            ->setMultipleHeaders($outboundMessage->getHeaders());

        if ($outboundMessage->getDeliveryDelay()) {
            $preparedMessage = $preparedMessage->setHeader(
                MessageHeaders::DELIVERY_DELAY,
                $outboundMessage->getDeliveryDelay()
            );
        }
        if ($outboundMessage->getPriority()) {
            $preparedMessage = $preparedMessage->setHeader(
                MessageHeaders::PRIORITY,
                $outboundMessage->getPriority()
            );
        }
        if ($outboundMessage->getTimeToLive()) {
            $preparedMessage = $preparedMessage->setHeader(
                MessageHeaders::TIME_TO_LIVE,
                $outboundMessage->getTimeToLive()
            );
        }

        return $preparedMessage->build();
    }
}
