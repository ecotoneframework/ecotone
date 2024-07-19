<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer\MessagePoller;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagePoller;
use Ecotone\Messaging\PollableChannel;
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

    public function receiveWithTimeout(int $timeoutInMilliseconds): ?Message
    {
        $message = $timeoutInMilliseconds
            ? $this->pollableChannel->receiveWithTimeout($timeoutInMilliseconds)
            : $this->pollableChannel->receive();

        if ($message) {
            $message = MessageBuilder::fromMessage($message)
                ->setHeader(MessageHeaders::POLLED_CHANNEL_NAME, $this->pollableChannelName)
                ->build();
        }
        return $message;
    }
}
