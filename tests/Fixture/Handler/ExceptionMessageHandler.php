<?php

namespace Fixture\Handler;

use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class ExceptionMessageHandler
 * @package Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ExceptionMessageHandler implements MessageHandler
{
    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        throw new \InvalidArgumentException("test");
    }

    public function __toString()
    {
        return self::class;
    }
}