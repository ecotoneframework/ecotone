<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint\MessageDrivenChannelAdapter;

/**
 * Interface MessageDrivenConsumer
 * @package SimplyCodedSoftware\Messaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageDrivenChannelAdapter
{
    public function startMessageDrivenConsumer() : void;
}