<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Collector;

use Ecotone\Messaging\Message;
use Psr\Log\LoggerInterface;

/**
 * This is responsible for collecting message in order to send them later.
 * This is useful in scenario where we given publisher is not transactional (e.g. SQS, Redis)
 * and we want to delay sending messages so it's done just before transaction is committed
 */
final class CollectorStorage
{
    /**
     * @param CollectedMessage[] $collectedMessages
     */
    public function __construct(
        private bool $enabled = false,
        private array $collectedMessages = []
    ) {
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->collectedMessages = [];
        $this->enabled = false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function collect(Message $message, LoggerInterface $logger): void
    {
        $logger->info(sprintf('Collecting message with id: %s', $message->getHeaders()->getMessageId()));
        $this->collectedMessages[] = $message;
    }

    /**
     * @return Message[]
     */
    public function releaseMessages(LoggerInterface $logger): array
    {
        $logger->info(sprintf('Releasing collected %s message(s) to send them to Message Channels', count($this->collectedMessages)));
        $collectedMessages = $this->collectedMessages;
        $this->disable();

        return $collectedMessages;
    }
}
