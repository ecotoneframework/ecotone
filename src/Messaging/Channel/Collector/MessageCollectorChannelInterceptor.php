<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Collector;

use Ecotone\Messaging\Channel\AbstractChannelInterceptor;
use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Psr\Log\LoggerInterface;

final class MessageCollectorChannelInterceptor extends AbstractChannelInterceptor implements ChannelInterceptor
{
    public function __construct(
        private CollectorStorage $collectorStorage,
        private LoggerInterface $logger
    ) {
    }

    public function preSend(Message $message, MessageChannel $messageChannel): ?Message
    {
        if ($this->collectorStorage->isEnabled()) {
            $this->collectorStorage->collect($message, $this->logger);

            $message = null;
        }

        return $message;
    }
}
