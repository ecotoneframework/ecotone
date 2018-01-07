<?php

namespace Messaging\Handler;

use Messaging\MessageChannel;
use Messaging\MessageHandler;

/**
 * Interface MessageHandlerBuilder
 * @package Messaging\Config
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