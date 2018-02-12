<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;

/**
 * Interface Gateway
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface GatewayBuilder
{
    /**
     * @return string
     */
    public function getReferenceName() : string;

    /**
     * @return string
     */
    public function getInputChannelName() : string;

    /**
     * @return string
     */
    public function getInterfaceName() : string;

    /**
     * @param ChannelResolver $channelResolver
     * @return object
     */
    public function build(ChannelResolver $channelResolver);
}