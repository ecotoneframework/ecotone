<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Rabbitmq;

use SimplyCodedSoftware\IntegrationMessaging\Rabbitmq\CachedRabbitmqConnectionFactory;

/**
 * Class CachedRabbitmqConnectionFactoryTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Rabbitmq
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CachedRabbitmqConnectionFactoryTest extends RabbitmqMessagingTest
{
//    public function test_using_same_instance_of_connection()
//    {
//        $cachedRabbitmqConnectionFactory = new CachedRabbitmqConnectionFactory($this->getRabbitConnectionFactory());
//
//        $this->assertEquals(
//            $cachedRabbitmqConnectionFactory->createConnection(),
//            $cachedRabbitmqConnectionFactory->createConnection()
//        );
//    }
}