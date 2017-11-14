<?php

namespace Messaging\Handler\Gateway\Poller;

use Messaging\Handler\Gateway\GatewayReply;
use Messaging\Handler\Gateway\ReplySender;

/**
 * Class TimeoutChannelReplySender
 * @package Messaging\Handler\Gateway\Poller
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TimeoutChannelReplySender implements ReplySender
{
    const MICROSECOND_TO_MILLI_SECOND = 1000;
    /**
     * @var \Messaging\Handler\Gateway\GatewayReply
     */
    private $gatewayReply;
    /**
     * @var int
     */
    private $millisecondsTimeout;

    /**
     * ReceivePoller constructor.
     * @param \Messaging\Handler\Gateway\GatewayReply $gatewayReply
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
    public function receiveAndForwardReply(): void
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