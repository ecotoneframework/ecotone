<?php

namespace Ecotone\Amqp;

use AMQPConnection;
use Ecotone\Enqueue\ReconnectableConnectionFactory;
use Ecotone\Messaging\Support\Assert;
use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\AmqpContext;
use Interop\Queue\Context;
use ReflectionClass;
use ReflectionProperty;

class AmqpConsumerConnectionFactory implements ReconnectableConnectionFactory
{
    /**
     * @var AmqpConnectionFactory
     */
    private $connectionFactory;

    public function __construct(AmqpConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    public function createContext(): Context
    {
        if (! $this->isConnected()) {
            $this->reconnect();
            ;
        }

        return $this->connectionFactory->createContext();
    }

    public function getConnectionInstanceId(): int
    {
        return spl_object_id($this->connectionFactory);
    }

    /**
     * @param Context|AmqpContext|null $context
     */
    public function isDisconnected(?Context $context): bool
    {
        if (! $context) {
            return false;
        }

        Assert::isSubclassOf($context, AmqpContext::class, 'Context must be ' . AmqpContext::class);

        return ! $context->getExtChannel()->isConnected();
    }

    public function reconnect(): void
    {
        $connectionProperty = $this->getConnectionProperty();

        /** @var AMQPConnection $connection */
        $connection = $connectionProperty->getValue($this->connectionFactory);
        if ($connection) {
            $connection->disconnect();
        }

        $connectionProperty->setValue($this->connectionFactory, null);
    }

    private function isConnected(): bool
    {
        $connectionProperty = $this->getConnectionProperty();
        /** @var AMQPConnection $connection */
        $connection = $connectionProperty->getValue($this->connectionFactory);

        return $connection ? $connection->isConnected() : false;
    }

    private function getConnectionProperty(): ReflectionProperty
    {
        $reflectionClass = new ReflectionClass($this->connectionFactory);

        $connectionProperty = $reflectionClass->getProperty('connection');
        $connectionProperty->setAccessible(true);

        return $connectionProperty;
    }
}
