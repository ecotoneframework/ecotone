<?php

namespace Test\Ecotone\Amqp;

use Interop\Amqp\AmqpConnectionFactory;
use Enqueue\AmqpExt\AmqpConnectionFactory as AmqpLibConnection;
use PHPUnit\Framework\TestCase;

/**
 * Class RabbitmqMessagingTest
 * @package Test\Ecotone\IntegrationMessaging\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class AmqpMessagingTest extends TestCase
{
    const RABBITMQ_HOST = 'localhost';

    const RABBITMQ_USER = 'guest';

    const RABBITMQ_PASSWORD = 'guest';

    /**
     * @return AmqpConnectionFactory
     */
    public function getCachedConnectionFactory() : AmqpConnectionFactory
    {
        return $this->getRabbitConnectionFactory();
    }

    /**
     * @return AmqpConnectionFactory
     */
    public function getRabbitConnectionFactory() : AmqpConnectionFactory
    {
        $host = getenv("RABBIT_HOST") ? getenv("RABBIT_HOST") : "localhost";
        $config = "amqp://{$host}:5672";

        return new AmqpLibConnection($config);
    }
}