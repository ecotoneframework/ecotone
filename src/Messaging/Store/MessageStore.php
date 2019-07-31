<?php

namespace Ecotone\Messaging\Store;

use Ramsey\Uuid\UuidInterface;
use Ecotone\Messaging\Message;

/**
 * Interface MessageStore - used in single message scenarios
 * @package Ecotone\Messaging\Store
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageStore
{
    /**
     * Creates group with group id equal to message id, containing single message
     *
     * @param Message $message
     */
    public function addSingleMessage(Message $message) : void;

    /**
     * Removes group containing single message
     *
     * @param UuidInterface $messageId
     */
    public function removeSingleMessage(UuidInterface $messageId) : void;

    /**
     * @param UuidInterface $messageId
     * @return Message|null
     */
    public function getSingleMessage(UuidInterface $messageId) : ?Message;

    /**
     * @return int
     */
    public function getSingleMessagesCount() : int;
}