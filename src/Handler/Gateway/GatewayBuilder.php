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
    public function setChannelResolver(ChannelResolver $channelResolver) : void;

    public function build();
}