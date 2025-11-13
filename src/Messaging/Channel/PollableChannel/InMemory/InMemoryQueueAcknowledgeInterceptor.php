<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\InMemory;

use Ecotone\Messaging\Channel\AbstractChannelInterceptor;
use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Channel\InMemoryStreamingChannel;
use Ecotone\Messaging\Endpoint\FinalFailureStrategy;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
final class InMemoryQueueAcknowledgeInterceptor extends AbstractChannelInterceptor implements ChannelInterceptor
{
    public const ECOTONE_IN_MEMORY_QUEUE_ACK = 'ecotone.in_memory_queue.ack';

    public function __construct(private FinalFailureStrategy $finalFailureStrategy, private bool $isAutoAcked)
    {

    }

    /**
     * @inheritDoc
     */
    public function preSend(Message $message, MessageChannel $messageChannel): ?Message
    {
        if ($message->getHeaders()->containsKey(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION)) {
            return $message;
        }

        Assert::isTrue($messageChannel instanceof PollableChannel, 'InMemoryQueueAcknowledgeInterceptor can be used only with PollableChannel');

        // Skip for shared channels - they add their own callback in receiveWithTimeout()
        if ($messageChannel instanceof InMemoryStreamingChannel) {
            return $message;
        }

        return MessageBuilder::fromMessage($message)
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, self::ECOTONE_IN_MEMORY_QUEUE_ACK)
            ->setHeader(self::ECOTONE_IN_MEMORY_QUEUE_ACK, new InMemoryAcknowledgeCallback(queueChannel: $messageChannel, message: $message, failureStrategy: $this->finalFailureStrategy, isAutoAcked: $this->isAutoAcked))
            ->build();
    }
}
