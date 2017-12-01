<?php

namespace Messaging\Handler;

use Messaging\Message;

/**
 * Interface MessageProcessor
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageProcessor
{
    /**
     * @param Message $message
     * @return mixed can return everything from null to object, string etc.
     */
    public function processMessage(Message $message);
}