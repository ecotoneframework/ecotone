<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use SimplyCodedSoftware\Messaging\PollableChannel;

/**
 * Class GatewayReply
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayReply
{
    /**
     * @var PollableChannel
     */
    private $replyChannel;
    /**
     * @var PollableChannel
     */
    private $responseChannel;

    /**
     * GatewayReply constructor.
     * @param PollableChannel $replyChannel
     * @param PollableChannel $responseChannel
     */
    public function __construct(PollableChannel $replyChannel, PollableChannel $responseChannel)
    {
        $this->replyChannel = $replyChannel;
        $this->responseChannel = $responseChannel;
    }

    public function replyChannel() : PollableChannel
    {
        return $this->replyChannel;
    }

    public function responseChannel() : PollableChannel
    {
        return $this->responseChannel;
    }
}