<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer\MessageDrivenChannelAdapter;

use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Interface MessageDrivenConsumer
 * @package SimplyCodedSoftware\Messaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageDrivenChannelAdapter
{
    /**
     * @param MessageHandler $onMessageCallback
     */
    public function startMessageDrivenConsumer(MessageHandler $onMessageCallback) : void;
}