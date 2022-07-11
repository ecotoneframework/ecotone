<?php

namespace Test\Ecotone\Amqp\Fixture;

use Ecotone\Messaging\SubscribableChannel;

/**
 * Interface AmqpConfigurationExample
 * @package Test\Ecotone\Amqp\Fixture
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AmqpConfigurationExample
{
    /**
     * @return SubscribableChannel
     */
    public function test(): SubscribableChannel;
}
