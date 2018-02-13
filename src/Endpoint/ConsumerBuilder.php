<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;

/**
 * Interface ConsumerBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConsumerBuilder
{
    /**
     * Will be called before building
     *
     * @param ChannelResolver $channelResolver
     * @return void
     */
    public function setChannelResolver(ChannelResolver $channelResolver) : void;

    /**
     * @return ConsumerLifecycle
     */
    public function build() : ConsumerLifecycle;
}