<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelService;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Throwable;

/**
 * licence Apache-2.0
 */
class PollingConsumerErrorChannelInterceptor
{
    public function __construct(
        private ErrorChannelService $errorChannelService,
        private ChannelResolver $channelResolver,
    ) {
    }

    public function handle(MethodInvocation $methodInvocation, Message $requestMessage)
    {
        try {
            return $methodInvocation->proceed();
        } catch (Throwable $exception) {
            if (! $this->tryToSendToErrorChannel($exception, $requestMessage)) {
                throw $exception;
            }
        }
    }

    private function tryToSendToErrorChannel(Throwable $exception, Message $requestMessage): bool
    {
        if ($requestMessage->getHeaders()->containsKey(MessageHeaders::CONSUMER_POLLING_METADATA)) {
            /** @var PollingMetadata $pollingMetadata */
            $pollingMetadata = $requestMessage->getHeaders()->get(MessageHeaders::CONSUMER_POLLING_METADATA);
            $errorChannelName = $pollingMetadata->getErrorChannelName();

            if (! $errorChannelName) {
                return false;
            }

            $this->errorChannelService->handle(
                $requestMessage,
                $exception,
                $this->channelResolver->resolve($errorChannelName),
                $requestMessage->getHeaders()->get(MessageHeaders::POLLED_CHANNEL_NAME)
            );

            return true;
        }

        return false;
    }
}
