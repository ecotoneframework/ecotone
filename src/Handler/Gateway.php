<?php

namespace Messaging\Handler;

use Messaging\Message;
use Messaging\MessageChannel;
use Messaging\MessagingRegistry;

/**
 * Class Gateway
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class Gateway
{
    /**
     * @var MessagingRegistry
     */
    private $messagingRegistry;

    /**
     * Gateway constructor.
     * @param MessagingRegistry $messagingRegistry
     */
    public function __construct(MessagingRegistry $messagingRegistry)
    {
        $this->messagingRegistry = $messagingRegistry;
    }

    /**
     * @param Message $message
     * @param MessageChannel $requestChannel
     */
    public function sendAndReceive(Message $message, MessageChannel $requestChannel)
    {


//        $replyChannel = $this->messagingRegistry->getMessageChannel($message->);
    }
}