<?php

namespace Messaging\Handler;

use Messaging\Message;
use Messaging\MessageHandler;

/**
 * Class ServiceActivator
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class ServiceActivatingHandler implements MessageHandler
{
    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        // TODO: Implement handle() method.
    }
}