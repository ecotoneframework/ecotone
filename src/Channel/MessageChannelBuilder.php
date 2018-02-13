<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Channel;

use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;

/**
 * Interface MessageChannelBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageChannelBuilder
{
    /**
     * @return string
     */
    public function getMessageChannelName() : string;

    /**
     * @return MessageChannel
     */
    public function build() : MessageChannel;
}