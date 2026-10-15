<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer\MessagePoller;

use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Messaging\MessagePoller;
use Ecotone\Api\Messaging\PollableChannel;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class PollingConsumerTaskExecutor
 * @package Ecotone\Messaging\Endpoint\PollingConsumer
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class PollableChannelPollerAdapter implements MessagePoller
{
    public function __construct(private string $pollableChannelName, private PollableChannel $pollableChannel)
    {
    }

    public function receiveWithTimeout(PollingMetadata $pollingMetadata): ?Message
    {
        $pollingMetadata = $pollingMetadata->withPolledChannelName($this->pollableChannelName);
        $timeoutInMilliseconds = $pollingMetadata->fixedRateInMilliseconds;
        $message = $timeoutInMilliseconds
            ? $this->pollableChannel->receiveWithTimeout($pollingMetadata)
            : $this->pollableChannel->receive();

        if ($message) {
            $message = MessageBuilder::fromMessage($message)
                ->setHeader(MessageHeaders::POLLED_CHANNEL_NAME, $this->pollableChannelName)
                ->build();
        }
        return $message;
    }

    public function onConsumerStop(): void
    {
        $this->pollableChannel->onConsumerStop();
    }
}
