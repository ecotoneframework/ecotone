<?php

namespace Ecotone\Messaging\Store;

use Ramsey\Uuid\UuidInterface;
use Ecotone\Messaging\Message;

/**
 * Interface PollableMessageGroupStore
 * @package Ecotone\Messaging\Store
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageGroupStore
{
    /**
     * @param string $groupId
     * @return int
     */
    public function messageGroupSize(string $groupId) : int;

    /**
     * @param string $groupId
     * @return array|Message[]
     */
    public function getMessageForGroup(string $groupId) : array;

    /**
     * @param string $groupId
     * @param Message $message
     */
    public function addMessageToGroup(string $groupId, Message $message) : void;

    /**
     * @param string $groupId
     * @param Message $message
     */
    public function removeMessageFromGroup(string $groupId, Message $message) : void;

    /**
     * @param string $groupId
     * @param UuidInterface $messageId
     */
    public function removeMessageFromGroupById(string $groupId, UuidInterface $messageId) : void;

    /**
     * @param string $groupId
     * @return Message|null
     */
    public function pollMessageFromGroup(string $groupId) : ?Message;
}