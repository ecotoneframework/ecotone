<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Interface PropertySetter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface PropertySetter
{
    /**
     * @param Message $message
     * @param Message $replyMessage
     *
     * @return mixed new payload
     */
    public function evaluate(Message $message, Message $replyMessage);
}