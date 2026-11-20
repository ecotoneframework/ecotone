<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Collector;

use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Execution\ExecutionScope;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;

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
     * @param Message[] $collectedMessages
     */
    public function __construct(
        private readonly bool $enabled = false,
        private readonly array $collectedMessages = []
    ) {
    }

    public function enable(): void
    {
        $this->collected()->isCollecting = true;
    }

    public function disable(): void
    {
        $collected = $this->collected();
        $collected->messages = [];
        $collected->isCollecting = false;
    }

    public function isEnabled(): bool
    {
        return $this->collected()->isCollecting;
    }

    public function collect(Message $message, LoggingGateway $logger): void
    {
        $logger->info(
            sprintf('Collecting message with id: %s', $message->getHeaders()->getMessageId()),
            $message
        );
        $this->collected()->messages[] = $message;
    }

    /**
     * @return Message[]
     */
    public function releaseMessages(LoggingGateway $logger, Message $message): array
    {
        $collectedMessages = $this->collected()->messages;
        if (count($collectedMessages) > 0) {
            $logger->info(sprintf('Releasing collected %s message(s) to send them to Message Channels', count($collectedMessages)), $message);
        }
        $this->disable();

        return $collectedMessages;
    }

    private function collected(): CollectedMessages
    {
        return ExecutionScope::stateOf($this, fn () => new CollectedMessages($this->enabled, $this->collectedMessages));
    }
}
