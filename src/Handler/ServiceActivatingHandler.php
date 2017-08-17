<?php

namespace Messaging\Handler;


use Messaging\Message;
use Messaging\MessageHandler;

/**
 * Class ServiceActivatingHandler - Responsible for activating chosen service
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatingHandler implements MessageHandler
{
    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        // TODO: Implement handle() method.
    }
}