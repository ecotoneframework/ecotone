<?php

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\MessageChannel;

/**
 * Interface ChannelResolver
 * @package SimplyCodedSoftware\Messaging\Handler
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