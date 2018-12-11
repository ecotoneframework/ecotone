<?php

namespace SimplyCodedSoftware\Messaging\Channel;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\PollableChannel;

/**
 * Class QueueChannel
 * @package SimplyCodedSoftware\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class QueueChannel implements PollableChannel
{
    /**
     * @var array|Message[]
     */
    private $queue = [];

    private function __construct()
    {
    }

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        $this->queue[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function receive(): ?Message
    {
        return array_pop($this->queue);
    }

    public function __toString()
    {
        return "queue channel";
    }
}