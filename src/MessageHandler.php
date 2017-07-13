<?php

namespace Messaging;

/**
 * Interface MessageHandler
 * @package Messaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageHandler
{
    /**
     * Handle given message
     *
     * @param Message $message
     * @throws \Exception
     */
    public function handle(Message $message) : void;
}