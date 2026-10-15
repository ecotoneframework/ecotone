<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Api\Messaging\ErrorMessage;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageChannel;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Channel\PollableChannel\Serialization\OutboundMessageConverter;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagatorInterceptor;
use Throwable;

/**
 * licence Apache-2.0
 */
final class ErrorChannelService
{
    public function __construct(
        private LoggingGateway $loggingGateway,
        private OutboundMessageConverter $outboundMessageConverter,
        private ConversionService $conversionService,
        private MessageHeadersPropagatorInterceptor $messageHeadersPropagator,
    ) {
    }

    public function handle(
        Message        $requestMessage,
        Throwable     $cause,
        MessageChannel $errorChannel,
        ?string        $relatedPolledChannelName,
        ?string $routingSlip = null,
    ) {
        $this->loggingGateway->error(
            'Error occurred during handling message. Sending Message to handle it in predefined Error Channel.',
            $requestMessage,
            ['exception' => $cause],
        );

        $outboundMessage = $this->outboundMessageConverter->prepare($requestMessage, $this->conversionService);
        $messageBuilder = MessageBuilder::withPayload($outboundMessage->getPayload())
            ->setMultipleHeaders($outboundMessage->getHeaders());

        if ($relatedPolledChannelName) {
            $messageBuilder = $messageBuilder->setHeader(MessageHeaders::POLLED_CHANNEL_NAME, $relatedPolledChannelName);
        }

        if ($routingSlip) {
            $messageBuilder = $messageBuilder->prependRoutingSlip([$routingSlip]);
        }

        $this->messageHeadersPropagator->storeHeaders(
            fn () => $errorChannel->send(
                ErrorMessage::create(
                    $messageBuilder->build(),
                    $cause
                )
            ),
            $requestMessage,
        );

        $this->loggingGateway->info(
            'Message was sent to Error Channel successfully.',
            $requestMessage,
            ['exception' => $cause],
        );
    }
}
