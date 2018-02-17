<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptor;

/**
 * Class NamedChannelInterceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class NamedOrderedChannelInterceptor
{
    public function hasName(string $channelName) : bool
    {

    }

    /**
     * Highest will be invoked before lower ones
     *
     * @return int
     */
    public function geOrderNumber() : int
    {

    }

    public function getChannelInterceptor() : ChannelInterceptor
    {

    }
}