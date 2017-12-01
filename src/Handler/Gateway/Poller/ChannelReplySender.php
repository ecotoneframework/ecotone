<?php

namespace Messaging\Handler\Gateway\Poller;

use Messaging\Handler\Gateway\GatewayReply;
use Messaging\Handler\Gateway\ReplySender;
use Messaging\Message;
use Messaging\PollableChannel;

/**
 * Class ReceivePoller
 * @package Messaging\Handler\Gateway\Poller
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChannelReplySender implements ReplySender
{
    /**
     * @var PollableChannel
     */
    private $replyChannel;

    /**
     * ReceivePoller constructor.
     * @param PollableChannel $replyChannel
     */
    public function __construct(PollableChannel $replyChannel)
    {
        $this->replyChannel = $replyChannel;
    }

    /**
     * @inheritDoc
     */
    public function receiveReply(): ?Message
    {
        $message = null;
        while (!$message) {
            $message = $this->replyChannel->receive();
        }

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function hasReply(): bool
    {
        return true;
    }
}