<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Channel;

use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;

/**
 * Interface ChannelInterceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Channel
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
     * @param bool $wasSuccessful
     */
    public function postSend(?Message $message, MessageChannel $messageChannel, bool $wasSuccessful) : void;

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