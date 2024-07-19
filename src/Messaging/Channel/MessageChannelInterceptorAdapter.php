<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\MessageChannel;

/**
 * Interface ChannelInterceptorAdapter
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface MessageChannelInterceptorAdapter extends MessageChannel
{
    /**
     * @return MessageChannel
     */
    public function getInternalMessageChannel(): MessageChannel;
}
