<?php

namespace Test\SimplyCodedSoftware\Messaging\Rabbitmq;

/**
 * Class ConnectionFactoryTest
 * @package Test\SimplyCodedSoftware\Messaging\Rabbitmq
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConnectionFactoryTest extends RabbitmqMessagingTest
{
    public function test_connecting_correctly_to_rabbit()
    {
        $connection = $this->getRabbitConnectionFactory()->createConnection();

        $this->assertTrue($connection->isConnected(), "Rabbitmq should be connected with default config");
    }
}