<?php

namespace SimplyCodedSoftware\Messaging\Rabbitmq;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class AmqpOutboundChannelAdapter
 * @package SimplyCodedSoftware\Messaging\Rabbitmq
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpOutboundChannelAdapter implements MessageHandler
{
    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        // TODO: Implement handle() method.
    }
}