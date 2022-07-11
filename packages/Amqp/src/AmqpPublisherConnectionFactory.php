<?php

namespace Ecotone\Amqp;

class AmqpPublisherConnectionFactory extends AmqpConsumerConnectionFactory
{
    public function getConnectionInstanceId(): int
    {
        return parent::getConnectionInstanceId() . 1;
    }
}
