<?php

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\MessageChannel;

/**
 * Interface ChannelResolver
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface ChannelResolver
{
    /**
     * @throws DestinationResolutionException
     */
    public function resolve(string|MessageChannel $channelName): MessageChannel;

    /**
     * @param string $channelName
     *
     * @return bool
     */
    public function hasChannelWithName(string $channelName): bool;
}
