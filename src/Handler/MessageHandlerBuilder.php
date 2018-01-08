<?php

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Interface MessageHandlerBuilder
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageHandlerBuilder
{
    /**
     * @return MessageHandler
     */
    public function build() : MessageHandler;

    /**
     * @return string
     */
    public function messageHandlerName() : string;

    /**
     * @return string
     */
    public function getInputMessageChannelName() : string;

    /**
     * @param ChannelResolver $channelResolver
     * @return MessageHandlerBuilder
     */
    public function setChannelResolver(ChannelResolver $channelResolver) : MessageHandlerBuilder;
}