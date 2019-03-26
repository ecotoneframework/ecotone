<?php

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\SubscribableChannel;

/**
 * Interface ChannelResolver
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ChannelResolver
{
    /**
     * @param string|MessageChannel $channelName
     * @return MessageChannel|PollableChannel|SubscribableChannel|DirectChannel
     *
     * @throws DestinationResolutionException
     */
    public function resolve($channelName) : MessageChannel;

    /**
     * @param string $channelName
     *
     * @return bool
     */
    public function hasChannelWithName(string $channelName) : bool;
}