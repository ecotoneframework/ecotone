<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\InMemory;

use Ecotone\Messaging\Channel\AbstractChannelInterceptor;
use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
final class InMemoryQueueAcknowledgeInterceptor extends AbstractChannelInterceptor implements ChannelInterceptor
{
    public const ECOTONE_IN_MEMORY_QUEUE_ACK = 'ecotone.in_memory_queue.ack';

    /**
     * @inheritDoc
     */
    public function preSend(Message $message, MessageChannel $messageChannel): ?Message
    {
        if ($message->getHeaders()->containsKey(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION)) {
            return $message;
        }

        return MessageBuilder::fromMessage($message)
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, self::ECOTONE_IN_MEMORY_QUEUE_ACK)
            ->setHeader(self::ECOTONE_IN_MEMORY_QUEUE_ACK, new InMemoryAcknowledgeCallback($messageChannel, $message))
            ->build();
    }
}
