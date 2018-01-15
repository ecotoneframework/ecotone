<?php

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;

/**
 * Interface PollableFactory
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConsumerFactory
{
    /**
     * @param ChannelResolver $channelResolver
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return bool
     */
    public function isSupporting(ChannelResolver $channelResolver, MessageHandlerBuilder $messageHandlerBuilder) : bool;

    /**
     * @param ChannelResolver $channelResolver
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return ConsumerLifecycle
     */
    public function create(ChannelResolver $channelResolver, MessageHandlerBuilder $messageHandlerBuilder) : ConsumerLifecycle;
}