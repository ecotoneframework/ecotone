<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\MessageChannel;

/**
 * Interface ChannelInterceptorAdapter
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageChannelInterceptorAdapter extends MessageChannel
{
    /**
     * @return MessageChannel
     */
    public function getInternalMessageChannel() : MessageChannel;
}