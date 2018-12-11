<?php

namespace SimplyCodedSoftware\Messaging\Handler\Splitter;

use SimplyCodedSoftware\Messaging\Message;

/**
 * Class DirectMessageSplitter
 * @package SimplyCodedSoftware\Messaging\Handler\Splitter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class DirectMessageSplitter
{
    /**
     * @param Message $message
     *
     * @return array
     */
    public function split(Message $message) : array
    {
        return $message->getPayload();
    }
}