<?php

namespace Ecotone\Messaging\Store;

use Ecotone\Messaging\Message;
use Ramsey\Uuid\UuidInterface;

/**
 * Interface MessageStore - used in single message scenarios
 * @package Ecotone\Messaging\Store
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface MessageStore
{
    /**
     * Creates group with group id equal to message id, containing single message
     *
     * @param Message $message
     */
    public function addSingleMessage(Message $message): void;

    /**
     * Removes group containing single message
     *
     * @param UuidInterface $messageId
     */
    public function removeSingleMessage(UuidInterface $messageId): void;

    /**
     * @param UuidInterface $messageId
     * @return Message|null
     */
    public function getSingleMessage(UuidInterface $messageId): ?Message;

    /**
     * @return int
     */
    public function getSingleMessagesCount(): int;
}
