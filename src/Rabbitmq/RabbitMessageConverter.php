<?php

namespace SimplyCodedSoftware\Messaging\Rabbitmq;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Interface MessageConverter
 * @package SimplyCodedSoftware\Messaging\Rabbitmq
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface RabbitMessageConverter
{
    /**
     * @param AMQPMessage $amqpMessage
     * @return mixed
     */
    public function toMessage(AMQPMessage $amqpMessage);

    /**
     * @param mixed $message
     * @return AMQPMessage
     */
    public function fromMessage($message) : AMQPMessage;
}