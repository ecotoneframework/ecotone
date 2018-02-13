<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Rabbitmq;

use SimplyCodedSoftware\IntegrationMessaging\Rabbitmq\RabbitmqConnectionFactory;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class RabbitmqMessagingTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Rabbitmq
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class RabbitmqMessagingTest extends MessagingTest
{
    const RABBITMQ_HOST = 'rabbitmq';

    const RABBITMQ_USER = 'user';

    const RABBITMQ_PASSWORD = 'password';


    public function getRabbitConnectionFactory() : RabbitmqConnectionFactory
    {
        return RabbitmqConnectionFactory::create()
            ->setHost(self::RABBITMQ_HOST)
            ->setUsername(self::RABBITMQ_USER)
            ->setPassword(self::RABBITMQ_PASSWORD);
    }
}