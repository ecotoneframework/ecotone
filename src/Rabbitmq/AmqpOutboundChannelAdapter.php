<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Rabbitmq;

use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class AmqpOutboundChannelAdapter
 * @package SimplyCodedSoftware\IntegrationMessaging\Rabbitmq
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