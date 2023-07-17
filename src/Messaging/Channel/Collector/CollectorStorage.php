<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Collector;

use Ecotone\Messaging\Message;

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
    public function __construct(private bool $enabled = false, private array $collectedMessages = [])
    {
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

    public function collect(Message $message): void
    {
        $this->collectedMessages[] = $message;
    }

    /**
     * @return Message[]
     */
    public function getCollectedMessages(): array
    {
        return $this->collectedMessages;
    }
}
