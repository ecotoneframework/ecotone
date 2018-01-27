<?php

namespace Test\SimplyCodedSoftware\Messaging\Rabbitmq;

use SimplyCodedSoftware\Messaging\Rabbitmq\CachedRabbitmqConnectionFactory;

/**
 * Class CachedRabbitmqConnectionFactoryTest
 * @package Test\SimplyCodedSoftware\Messaging\Rabbitmq
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CachedRabbitmqConnectionFactoryTest extends RabbitmqMessagingTest
{
    public function test_using_same_instance_of_connection()
    {
        $cachedRabbitmqConnectionFactory = new CachedRabbitmqConnectionFactory($this->getRabbitConnectionFactory());

        $this->assertEquals(
            $cachedRabbitmqConnectionFactory->createConnection(),
            $cachedRabbitmqConnectionFactory->createConnection()
        );
    }
}