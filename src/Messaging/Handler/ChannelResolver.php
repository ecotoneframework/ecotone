<?php

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\SubscribableChannel;

/**
 * Interface ChannelResolver
 * @package Ecotone\Messaging\Handler
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