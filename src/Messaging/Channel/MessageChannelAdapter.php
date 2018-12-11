<?php

namespace SimplyCodedSoftware\Messaging\Channel;

use SimplyCodedSoftware\Messaging\MessageChannel;

/**
 * Interface ChannelInterceptorAdapter
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageChannelAdapter extends MessageChannel
{
    /**
     * @return MessageChannel
     */
    public function getInternalMessageChannel() : MessageChannel;
}