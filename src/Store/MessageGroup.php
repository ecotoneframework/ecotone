<?php

namespace Messaging\Store;
use Messaging\Message;
use Messaging\MessagingException;

/**
 * Interface MessageGroup - used in multiple messages scenarios
 * @package Messaging\Store
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageGroup
{
    /**
     * @param Message $message
     * @throws MessagingException
     */
    public function add(Message $message) : void;

    /**
     * @param Message $message
     */
    public function remove(Message $message) : void;

    /**
     * @param Message $message
     * @return bool
     */
    public function canBeAdded(Message $message) : bool;

    /**
     * @return array|Message[]
     */
    public function messages() : array;

    /**
     * @return string
     */
    public function groupId() : string;

    /**
     * @return bool
     */
    public function isEmpty() : bool;

    /**
     * @return int
     */
    public function size() : int;

    /**
     * Clears group from messages
     */
    public function clear() : void;
}