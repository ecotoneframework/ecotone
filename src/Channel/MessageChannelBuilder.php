<?php

namespace Messaging\Channel;

use Messaging\MessageChannel;

/**
 * Interface MessageChannelBuilder
 * @package Messaging\Channel
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