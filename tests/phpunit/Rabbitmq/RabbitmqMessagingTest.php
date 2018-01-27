<?php

namespace Test\SimplyCodedSoftware\Messaging\Rabbitmq;

use SimplyCodedSoftware\Messaging\Rabbitmq\RabbitmqConnectionFactory;
use Test\SimplyCodedSoftware\Messaging\MessagingTest;

/**
 * Class RabbitmqMessagingTest
 * @package Test\SimplyCodedSoftware\Messaging\Rabbitmq
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