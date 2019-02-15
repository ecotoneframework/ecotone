<?php

namespace Test\SimplyCodedSoftware\Amqp;

use Interop\Amqp\AmqpConnectionFactory;
use Enqueue\AmqpLib\AmqpConnectionFactory as AmqpLibConnection;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Amqp\CachedAmqpConnectionFactory;

/**
 * Class RabbitmqMessagingTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class AmqpMessagingTest extends TestCase
{
    const RABBITMQ_HOST = 'rabbitmq';

    const RABBITMQ_USER = 'guest';

    const RABBITMQ_PASSWORD = 'guest';

    /**
     * @return AmqpConnectionFactory
     */
    public function getCachedConnectionFactory() : AmqpConnectionFactory
    {
        return new CachedAmqpConnectionFactory($this->getRabbitConnectionFactory());
    }

    /**
     * @return AmqpConnectionFactory
     */
    public function getRabbitConnectionFactory() : AmqpConnectionFactory
    {
        $config = [
            "dsn" => "amqp://rabbitmq:5672"
        ];

        return new AmqpLibConnection($config);
    }
}