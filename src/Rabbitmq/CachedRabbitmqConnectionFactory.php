<?php

namespace SimplyCodedSoftware\Messaging\Rabbitmq;

use PhpAmqpLib\Connection\AbstractConnection;

/**
 * Class CachedRabbitmqConnectionFactory
 * @package SimplyCodedSoftware\Messaging\Rabbitmq
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CachedRabbitmqConnectionFactory implements ConnectionFactory
{
    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;
    /**
     * @var AbstractConnection
     */
    private $currentConnection;

    /**
     * CachedRabbitmqConnectionFactory constructor.
     * @param ConnectionFactory $connectionFactory
     */
    public function __construct(ConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function createConnection(): AbstractConnection
    {
        if (!$this->currentConnection) {
            $this->currentConnection = $this->connectionFactory->createConnection();
        }

        return $this->currentConnection;
    }
}