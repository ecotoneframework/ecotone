<?php

namespace Fixture\Handler;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class DumbMessageHandler
 * @package Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class NoReturnMessageHandler implements MessageHandler
{
    /** @var  bool */
    private $wasCalled = false;

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $this->wasCalled = true;
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