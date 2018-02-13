<?php

namespace Fixture\Handler;

use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageProcessor;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class NoReplyMessageProducer
 * @package Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class NoReplyMessageProducer implements MessageProcessor
{
    private $wasCalled = false;

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message)
    {
        $this->wasCalled = true;
        return null;
    }

    public function wasCalled() : bool
    {
        return $this->wasCalled;
    }

    public function __toString()
    {
        return self::class;
    }
}