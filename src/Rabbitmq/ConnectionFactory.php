<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Rabbitmq;

use PhpAmqpLib\Connection\AbstractConnection;

/**
 * Interface ConnectionFactory
 * @package SimplyCodedSoftware\IntegrationMessaging\Rabbitmq
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConnectionFactory
{
    /**
     * @return AbstractConnection
     */
    public function createConnection() : AbstractConnection;
}