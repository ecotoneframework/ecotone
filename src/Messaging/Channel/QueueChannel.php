<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\PollableChannel;

/**
 * Class QueueChannel
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class QueueChannel implements PollableChannel
{
    /**
     * @var Message[]
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

    /**
     * @inheritDoc
     */
    public function receiveWithTimeout(int $timeoutInMilliseconds): ?Message
    {
        return $this->receive();
    }

    public function __toString()
    {
        return "queue channel";
    }
}