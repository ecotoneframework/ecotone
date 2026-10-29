<?php

namespace Ecotone\Messaging\Store;

use Ecotone\Messaging\Message;
use Ramsey\Uuid\UuidInterface;

/**
 * Interface PollableMessageGroupStore
 * @package Ecotone\Messaging\Store
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface MessageGroupStore
{
    /**
     * @param string $groupId
     * @return int
     */
    public function messageGroupSize(string $groupId): int;

    /**
     * @param string $groupId
     * @return array|Message[]
     */
    public function getMessageForGroup(string $groupId): array;

    /**
     * @param string $groupId
     * @param Message $message
     */
    public function addMessageToGroup(string $groupId, Message $message): void;

    /**
     * @param string $groupId
     * @param Message $message
     */
    public function removeMessageFromGroup(string $groupId, Message $message): void;

    /**
     * @param string $groupId
     * @param UuidInterface $messageId
     */
    public function removeMessageFromGroupById(string $groupId, UuidInterface $messageId): void;

    /**
     * @param string $groupId
     * @return Message|null
     */
    public function pollMessageFromGroup(string $groupId): ?Message;
}
