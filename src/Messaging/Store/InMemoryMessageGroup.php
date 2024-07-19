<?php

namespace Ecotone\Messaging\Store;

use Ecotone\Messaging\Message;
use Ramsey\Uuid\Uuid;

/**
 * Class InMemoryMessageGroup
 * @package Ecotone\Messaging\Store
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
final class InMemoryMessageGroup implements MessageGroup
{
    private array $messages;
    private string $groupId;

    /**
     * InMemoryMessageGroup constructor.
     * @param string $groupId
     * @param array|Message[] $messages
     */
    private function __construct(string $groupId, array $messages)
    {
        $this->groupId = $groupId;
        $this->messages = $messages;
    }

    public static function createEmpty(): InMemoryMessageGroup
    {
        return new self(Uuid::uuid4()->toString(), []);
    }

    /**
     * @param string $groupId
     */
    public static function createEmptyWithId(string $groupId): InMemoryMessageGroup
    {
        return new self($groupId, []);
    }

    /**
     * @inheritDoc
     */
    public function add(Message $messageToAdd): void
    {
        foreach ($this->messages as $message) {
            if ($this->areMessagesEqual($messageToAdd, $message)) {
                return;
            }
        }

        $this->messages[] = $messageToAdd;
    }

    /**
     * @param Message $messageToRemove
     * @param $message
     * @return bool
     */
    private function areMessagesEqual(Message $messageToRemove, $message): bool
    {
        return $message == $messageToRemove;
    }

    /**
     * @inheritDoc
     */
    public function remove(Message $messageToRemove): void
    {
        $repackedMessages = [];

        foreach ($this->messages as $message) {
            if ($this->areMessagesEqual($messageToRemove, $message)) {
                continue;
            }

            $repackedMessages[] = $message;
        }

        $this->messages = $repackedMessages;
    }

    /**
     * @inheritDoc
     */
    public function canBeAdded(Message $message): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function messages(): array
    {
        return $this->messages;
    }

    /**
     * @inheritDoc
     */
    public function groupId(): string
    {
        return $this->groupId;
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return empty($this->messages);
    }

    /**
     * @inheritDoc
     */
    public function size(): int
    {
        return count($this->messages);
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->messages = [];
    }
}
