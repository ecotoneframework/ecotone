<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;

/**
 * Interface Gateway
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
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
    public function getRequestChannelName() : string;

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