<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Collector;

use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Message;

/**
 * This is responsible for collecting message in order to send them later.
 * This is useful in scenario where we given publisher is not transactional (e.g. SQS, Redis)
 * and we want to delay sending messages so it's done just before transaction is committed
 */
/**
 * licence Apache-2.0
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

    public function collect(Message $message, LoggingGateway $logger): void
    {
        $logger->info(
            sprintf('Collecting message with id: %s', $message->getHeaders()->getMessageId()),
            $message
        );
        $this->collectedMessages[] = $message;
    }

    /**
     * @return Message[]
     */
    public function releaseMessages(LoggingGateway $logger, Message $message): array
    {
        if (count($this->collectedMessages) > 0) {
            $logger->info(sprintf('Releasing collected %s message(s) to send them to Message Channels', count($this->collectedMessages)), $message);
        }
        $collectedMessages = $this->collectedMessages;
        $this->disable();

        return $collectedMessages;
    }
}
