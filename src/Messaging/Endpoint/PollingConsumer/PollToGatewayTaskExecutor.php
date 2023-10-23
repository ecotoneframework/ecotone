<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\NonProxyGateway;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagePoller;
use Ecotone\Messaging\Scheduling\TaskExecutor;
use Ecotone\Messaging\Support\MessageBuilder;

class PollToGatewayTaskExecutor implements TaskExecutor
{
    public function __construct(
        private MessagePoller $messagePoller,
        private NonProxyGateway $gateway,
    ) {
    }

    public function execute(PollingMetadata $pollingMetadata): void
    {
        $message = $this->messagePoller->receiveWithTimeout($pollingMetadata->getExecutionTimeLimitInMilliseconds());
        if ($message) {
            $message = MessageBuilder::fromMessage($message)
                ->setHeader(MessageHeaders::CONSUMER_POLLING_METADATA, $pollingMetadata)
                ->build();
            $this->gateway->execute([$message]);
        }
    }
}
