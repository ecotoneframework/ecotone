<?php


namespace Ecotone\Dbal;


use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Ecotone\Enqueue\ReconnectableConnectionFactory;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;
use ReflectionClass;

class DbalReconnectableConnectionFactory implements ReconnectableConnectionFactory
{
    const CONNECTION_PROPERTIES = ["connection", "_conn"];

    private DbalConnectionFactory|ManagerRegistryConnectionFactory $connectionFactory;

    public function __construct(DbalConnectionFactory|ManagerRegistryConnectionFactory $dbalConnectionFactory)
    {
        $this->connectionFactory = $dbalConnectionFactory;
    }

    public function createContext(): Context
    {
        $this->reconnect();

        return $this->connectionFactory->createContext();
    }

    public function getConnectionInstanceId(): int
    {
        return spl_object_id($this->connectionFactory);
    }

    /**
     * @param Context|null|DbalContext $context
     * @return bool
     */
    public function isDisconnected(?Context $context): bool
    {
        if (!$context) {
            return false;
        }

        return !$context->getDbalConnection()->isConnected()  || (method_exists(Connection::class, "ping") && !$context->getDbalConnection()->ping());
    }

    public function reconnect(): void
    {
        $connection = $this->getConnection();

        if ($connection) {
            $connection->close();
            $connection->connect();
        }
    }

    public function getConnection() : Connection
    {
        return self::getWrappedConnection($this->connectionFactory);
    }

    /**
     * @param ManagerRegistryConnectionFactory|Connection $connection
     */
    public static function getWrappedConnection(object $connection): Connection
    {
        if ($connection instanceof ManagerRegistryConnectionFactory) {
            list($registry, $connectionName) = self::getManagerRegistryAndConnectionName($connection);
            /** @var Connection $connection */
            return $registry->getConnection($connectionName);
        }else {
            $reflectionClass   = new ReflectionClass($connection);
            $method = $reflectionClass->getMethod("establishConnection");
            $method->setAccessible(true);
            $method->invoke($connection);

            foreach ($reflectionClass->getProperties() as $property) {
                foreach (self::CONNECTION_PROPERTIES as $connectionPropertyName) {
                    if ($property->getName() === $connectionPropertyName) {
                        $connectionProperty = $reflectionClass->getProperty($connectionPropertyName);
                        $connectionProperty->setAccessible(true);
                        /** @var Connection $connection */
                        return $connectionProperty->getValue($connection);
                    }
                }
            }

            throw InvalidArgumentException::create("Did not found connection property in " . $reflectionClass->getName());
        }
    }

    public static function getManagerRegistryAndConnectionName(ManagerRegistryConnectionFactory $connectionFactory): array
    {
        $reflectionClass   = new ReflectionClass($connectionFactory);

        $registry = $reflectionClass->getProperty("registry");
        $registry->setAccessible(true);
        $config = $reflectionClass->getProperty("config");
        $config->setAccessible(true);

        $connectionName = $config->getValue($connectionFactory)["connection_name"];
        /** @var ManagerRegistry $registry */
        $registry = $registry->getValue($connectionFactory);

        return array($registry, $connectionName);
    }
}