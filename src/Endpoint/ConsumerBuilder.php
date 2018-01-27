<?php

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;

/**
 * Interface ConsumerBuilder
 * @package SimplyCodedSoftware\Messaging\Endpoint
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