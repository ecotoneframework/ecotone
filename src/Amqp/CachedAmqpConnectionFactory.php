<?php

namespace SimplyCodedSoftware\Amqp;

use Interop\Amqp\AmqpConnectionFactory;
use Interop\Queue\Context;

/**
 * Class CachedAmqpConnectionFactory
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CachedAmqpConnectionFactory implements AmqpConnectionFactory
{
    /**
     * @var AmqpConnectionFactory
     */
    private $connectionFactory;
    /**
     * @var Context
     */
    private $currentConnection;

    /**
     * CachedRabbitmqConnectionFactory constructor.
     * @param AmqpConnectionFactory $connectionFactory
     */
    public function __construct(AmqpConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * @return Context
     */
    public function createContext(): Context
    {
//        if (!$this->currentConnection) {
            $this->currentConnection = $this->connectionFactory->createContext();
//        }

        return $this->currentConnection;
    }
}