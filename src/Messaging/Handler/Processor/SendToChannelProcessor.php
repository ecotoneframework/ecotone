<?php

namespace Ecotone\Messaging\Handler\Processor;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;

/**
 * @licence Apache-2.0
 */
class SendToChannelProcessor implements MessageProcessor
{
    public function __construct(
        private MessageChannel $channel,
    ) {
    }

    public function process(Message $message): ?Message
    {
        $this->channel->send($message);
        return null;
    }
}
