<?php

namespace Ecotone\Messaging\Store;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ramsey\Uuid\UuidInterface;

/**
 * Class SimpleMessageStore
 * @package Ecotone\Messaging\Store
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
final class SimpleMessageStore implements MessageStore, MessageGroupStore
{
    private array $messages;
    private array $groups;

    /**
     * SimpleMessageStore constructor.
     * @param array|Message[] $messages
     * @param array|MessageGroup[] $groups
     */
    private function __construct(array $messages, array $groups)
    {
        $this->messages = $messages;
        $this->groups = $groups;
    }

    /**
     * @return SimpleMessageStore
     */
    public static function createEmpty(): self
    {
        return new self([], []);
    }

    /**
     * @inheritDoc
     */
    public function addSingleMessage(Message $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function removeSingleMessage(UuidInterface $messageId): void
    {
        // TODO: Implement removeSingleMessage() method.
    }

    /**
     * @inheritDoc
     */
    public function getSingleMessage(UuidInterface $messageId): ?Message
    {
        foreach ($this->messages as $message) {
            if ($messageId->toString() === $message->getHeaders()->get(MessageHeaders::MESSAGE_ID)) {
                return $message;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSingleMessagesCount(): int
    {
        return count($this->messages);
    }

    /**
     * @inheritDoc
     */
    public function messageGroupSize(string $groupId): int
    {
        /** @TODO */
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function addMessageToGroup(string $groupId, Message $message): void
    {
        // TODO: Implement addMessageToGroup() method.
    }

    /**
     * @inheritDoc
     */
    public function pollMessageFromGroup(string $groupId): ?Message
    {
        /** @TODO */
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getMessageForGroup(string $groupId): array
    {
        /** @TODO */
        return [];
    }

    /**
     * @inheritDoc
     */
    public function removeMessageFromGroup(string $groupId, Message $message): void
    {
        // TODO: Implement removeMessageFromGroup() method.
    }

    /**
     * @inheritDoc
     */
    public function removeMessageFromGroupById(string $groupId, UuidInterface $messageId): void
    {
        // TODO: Implement removeMessageFromGroupById() method.
    }
}
