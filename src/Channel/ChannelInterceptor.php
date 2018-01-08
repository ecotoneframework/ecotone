<?php

namespace SimplyCodedSoftware\Messaging\Channel;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;

/**
 * Interface ChannelInterceptor
 * @package SimplyCodedSoftware\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ChannelInterceptor
{
    /**
     * @param Message|null $message
     * @param MessageChannel $messageChannel message channel that message will be send to
     * @return Message|null
     */
    public function preSend(?Message $message, MessageChannel $messageChannel) : ?Message;

    /**
     * @param Message|null $message
     * @param MessageChannel $messageChannel message channel that message was sent to
     */
    public function postSend(?Message $message, MessageChannel $messageChannel) : void;

    /**
     * Before receiving from subscription channel
     *
     * @param MessageChannel $messageChannel
     */
    public function preReceive(MessageChannel $messageChannel) : void;

    /**
     * @param Message|null $message message that was received
     * @param MessageChannel $messageChannel message channel that message was received from
     */
    public function postReceive(?Message $message, MessageChannel $messageChannel) : void;
}