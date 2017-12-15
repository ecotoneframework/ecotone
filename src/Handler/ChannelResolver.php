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
     * @param string $channelName
     * @return MessageChannel
     *
     * @throws DestinationResolutionException
     */
    public function resolve(string $channelName) : MessageChannel;
}