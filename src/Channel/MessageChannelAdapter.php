<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Channel;

use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;

/**
 * Interface ChannelInterceptorAdapter
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageChannelAdapter extends MessageChannel
{
    /**
     * @return MessageChannel
     */
    public function getInternalMessageChannel() : MessageChannel;
}