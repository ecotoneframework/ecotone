<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\SubscribableChannel;

/**
 * Interface ChannelResolver
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler
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
}