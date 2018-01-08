<?php

namespace Fixture\Handler;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class StatefulHandler
 * @package Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StatefulHandler implements MessageHandler
{
    /**
     * @var Message|null
     */
    private $message;

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $this->message = $message;
    }

    public function message() : ?Message
    {
        return $this->message;
    }
}