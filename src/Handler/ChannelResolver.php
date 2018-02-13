<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;

/**
 * Interface ChannelResolver
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler
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