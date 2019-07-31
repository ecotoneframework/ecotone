<?php

namespace Ecotone\Messaging\Store;

use Ramsey\Uuid\Uuid;
use Ecotone\Messaging\Message;

/**
 * Class InMemoryMessageGroup
 * @package Ecotone\Messaging\Store
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class InMemoryMessageGroup implements MessageGroup
{
    /**
     * @var array|Message[]
     */
    private $messages;
    /**
     * @var string
     */
    private $groupId;

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

    /**
     * @return MessageGroup
     */
    public static function createEmpty() : MessageGroup
    {
        return new self(Uuid::uuid4()->toString(), []);
    }

    /**
     * @param string $groupId
     * @return MessageGroup
     */
    public static function createEmptyWithId(string $groupId) : MessageGroup
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

    /**
     * @param Message $messageToRemove
     * @param $message
     * @return bool
     */
    private function areMessagesEqual(Message $messageToRemove, $message): bool
    {
        return $message == $messageToRemove;
    }
}