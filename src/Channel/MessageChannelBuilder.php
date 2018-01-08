<?php

namespace SimplyCodedSoftware\Messaging\Channel;

use SimplyCodedSoftware\Messaging\MessageChannel;

/**
 * Interface MessageChannelBuilder
 * @package SimplyCodedSoftware\Messaging\Channel
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