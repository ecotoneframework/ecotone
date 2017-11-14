<?php

namespace Messaging\Handler\Gateway\Poller;

use Messaging\Handler\Gateway\GatewayReply;
use Messaging\Handler\Gateway\ReplySender;

/**
 * Class ReceivePoller
 * @package Messaging\Handler\Gateway\Poller
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChannelReplySender implements ReplySender
{
    /**
     * @var GatewayReply
     */
    private $gatewayReply;

    /**
     * ReceivePoller constructor.
     * @param GatewayReply $gatewayReply
     */
    public function __construct(GatewayReply $gatewayReply)
    {
        $this->gatewayReply = $gatewayReply;
    }

    /**
     * @inheritDoc
     */
    public function receiveAndForwardReply(): void
    {
        $message = null;
        while (!$message) {
            $message = $this->gatewayReply->replyChannel()->receive();

            if ($message) {
                $this->gatewayReply->responseChannel()->send($message);
            }
        }
    }
}