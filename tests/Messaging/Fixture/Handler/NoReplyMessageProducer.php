<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Message;

/**
 * Class NoReplyMessageProducer
 * @package Test\Ecotone\Messaging\Fixture\Handler
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