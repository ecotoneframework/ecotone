<?php

namespace Messaging\Handler\Poller;

use Messaging\Handler\GatewayReply;
use Messaging\Handler\ReplySender;

/**
 * Class TimeoutChannelReplySender
 * @package Messaging\Handler\Poller
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TimeoutChannelReplySender implements ReplySender
{
    const MICROSECOND_TO_MILLI_SECOND = 1000;
    /**
     * @var GatewayReply
     */
    private $gatewayReply;
    /**
     * @var int
     */
    private $millisecondsTimeout;

    /**
     * ReceivePoller constructor.
     * @param GatewayReply $gatewayReply
     * @param int $millisecondsTimeout
     */
    public function __construct(GatewayReply $gatewayReply, int $millisecondsTimeout)
    {
        $this->gatewayReply = $gatewayReply;
        $this->millisecondsTimeout = $millisecondsTimeout;
    }

    /**
     * @inheritDoc
     */
    public function receiveReply(): void
    {
        $message = null;
        $startingTimestamp = $this->currentMillisecond();

        while (($this->currentMillisecond() - $startingTimestamp) <= $this->millisecondsTimeout && is_null($message)) {
            $message = $this->gatewayReply->replyChannel()->receive();

            if ($message) {
                $this->gatewayReply->responseChannel()->send($message);
            }
        }
    }

    /**
     * @return float
     */
    private function currentMillisecond(): float
    {
        return microtime(true) * self::MICROSECOND_TO_MILLI_SECOND;
    }
}