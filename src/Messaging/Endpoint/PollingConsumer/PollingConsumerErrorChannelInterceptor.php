<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\ErrorMessage;
use Throwable;

/**
 * licence Apache-2.0
 */
class PollingConsumerErrorChannelInterceptor
{
    public function __construct(private ChannelResolver $channelResolver, private LoggingGateway $loggingGateway)
    {
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

            if ($errorChannelName && $this->channelResolver->hasChannelWithName($errorChannelName)) {
                $this->loggingGateway->critical(
                    sprintf('Error occurred during handling message. Sending Message to handle it in predefined Error Channel: `%s`.', $errorChannelName),
                    $requestMessage,
                    ['exception' => $exception],
                );

                $errorChannel = $this->channelResolver->resolve($errorChannelName);
                $errorChannel->send(ErrorMessage::create(MessageHandlingException::fromOtherException($exception, $requestMessage)));

                $this->loggingGateway->info(
                    sprintf('Message was sent to Error Channel: `%s` successfully.', $errorChannelName),
                    $requestMessage,
                    ['exception' => $exception],
                );

                return true;
            }
        }

        return false;
    }
}
