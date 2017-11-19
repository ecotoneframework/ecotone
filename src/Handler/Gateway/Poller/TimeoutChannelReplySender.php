<?php

namespace Messaging\Handler\Gateway\Poller;

use Messaging\Handler\Gateway\GatewayReply;
use Messaging\Handler\Gateway\ReplySender;
use Messaging\Message;
use Messaging\PollableChannel;

/**
 * Class TimeoutChannelReplySender
 * @package Messaging\Handler\Gateway\Poller
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TimeoutChannelReplySender implements ReplySender
{
    const MICROSECOND_TO_MILLI_SECOND = 1000;
    /**
     * @var PollableChannel
     */
    private $replyChannel;
    /**
     * @var int
     */
    private $millisecondsTimeout;

    /**
     * ReceivePoller constructor.
     * @param PollableChannel $replyChannel
     * @param int $millisecondsTimeout
     */
    public function __construct(PollableChannel $replyChannel, int $millisecondsTimeout)
    {
        $this->replyChannel = $replyChannel;
        $this->millisecondsTimeout = $millisecondsTimeout;
    }

    /**
     * @inheritDoc
     */
    public function receiveReply(): ?Message
    {
        $message = null;
        $startingTimestamp = $this->currentMillisecond();

        while (($this->currentMillisecond() - $startingTimestamp) <= $this->millisecondsTimeout && is_null($message)) {
            $message = $this->replyChannel->receive();
        }

        return $message;
    }

    /**
     * @return float
     */
    private function currentMillisecond(): float
    {
        return microtime(true) * self::MICROSECOND_TO_MILLI_SECOND;
    }

    /**
     * @inheritDoc
     */
    public function hasReply(): bool
    {
        return true;
    }
}