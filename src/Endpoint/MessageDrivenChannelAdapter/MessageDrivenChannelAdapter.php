<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingConsumer\MessageDrivenChannelAdapter;

use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Interface MessageDrivenConsumer
 * @package SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageDrivenChannelAdapter
{
    /**
     * @param MessageHandler $onMessageCallback
     */
    public function startMessageDrivenConsumer(MessageHandler $onMessageCallback) : void;
}