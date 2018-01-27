<?php

namespace SimplyCodedSoftware\Messaging\Rabbitmq;

use PhpAmqpLib\Connection\AbstractConnection;

/**
 * Interface ConnectionFactory
 * @package SimplyCodedSoftware\Messaging\Rabbitmq
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConnectionFactory
{
    /**
     * @return AbstractConnection
     */
    public function createConnection() : AbstractConnection;
}