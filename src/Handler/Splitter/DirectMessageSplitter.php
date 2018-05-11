<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Splitter;

use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class DirectMessageSplitter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Splitter
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