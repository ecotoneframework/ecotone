<?php

namespace Messaging\Handler;

use Messaging\MessageChannel;

/**
 * Interface ChannelResolver
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ChannelResolver
{
    /**
     * @param string|MessageChannel $channelName
     * @return MessageChannel
     *
     * @throws DestinationResolutionException
     */
    public function resolve($channelName) : MessageChannel;
}