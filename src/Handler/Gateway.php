<?php

namespace Messaging\Handler;

use Messaging\Channel\DirectChannel;
use Messaging\Message;
use Messaging\MessageHandler;
use Messaging\PollableChannel;

/**
 * Class Gateway
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class Gateway implements MessageHandler
{
    /**
     * @var DirectChannel
     */
    private $requestChannel;
    /**
     * @var ReplySender
     */
    private $replySender;

    /**
     * Gateway constructor.
     * @param DirectChannel $requestChannel
     * @param ReplySender $replySender
     */
    public function __construct(DirectChannel $requestChannel, ReplySender $replySender)
    {
        $this->requestChannel = $requestChannel;
        $this->replySender = $replySender;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $this->requestChannel->send($message);

        $this->replySender->receiveReply();
    }
}