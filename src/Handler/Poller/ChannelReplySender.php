<?php

namespace Messaging\Handler\Poller;

use Messaging\Handler\GatewayReply;
use Messaging\Handler\ReplySender;

/**
 * Class ReceivePoller
 * @package Messaging\Handler\Poller
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
    public function receiveReply(): void
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