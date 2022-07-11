<?php

namespace Ecotone\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;

class DbalConnection implements ManagerRegistry
{
    private function __construct(private ?Connection $connection, private ?EntityManagerInterface $entityManager = null)
    {
    }

    public static function fromConnectionFactory(DbalConnectionFactory $dbalConnectionFactory): ManagerRegistryConnectionFactory
    {
        return new ManagerRegistryConnectionFactory(new self($dbalConnectionFactory->createContext()->getDbalConnection()));
    }

    public static function create(Connection $connection): ManagerRegistryConnectionFactory
    {
        return new ManagerRegistryConnectionFactory(new self($connection));
    }

    public static function createEntityManager(EntityManagerInterface $entityManager): ManagerRegistryConnectionFactory
    {
        return new ManagerRegistryConnectionFactory(new self($entityManager->getConnection(), $entityManager));
    }

    public static function createForManagerRegistry(ManagerRegistry $managerRegistry, string $connectionName): ManagerRegistryConnectionFactory
    {
        return new ManagerRegistryConnectionFactory($managerRegistry, ['connection_name' => $connectionName]);
    }

    public function getDefaultConnectionName()
    {
        return 'default';
    }

    public function getConnection($name = null)
    {
        return $this->connection;
    }

    public function getConnections()
    {
        return [$this->connection];
    }

    public function getConnectionNames()
    {
        return ['default'];
    }

    public function getDefaultManagerName()
    {
        return 'default';
    }

    public function getManager($name = null)
    {
        return $this->entityManager;
    }

    public function getManagers()
    {
        return $this->entityManager ? [$this->entityManager] : [];
    }

    public function resetManager($name = null)
    {
        $this->entityManager->getUnitOfWork()->clear();

        return $this->entityManager;
    }

    public function getAliasNamespace($alias)
    {
        throw InvalidArgumentException::create('Method not supported');
    }

    public function getManagerNames()
    {
        return ['default'];
    }

    public function getRepository($persistentObject, $persistentManagerName = null)
    {
        return $this->entityManager->getRepository($persistentObject);
    }

    public function getManagerForClass($class)
    {
        return $this->entityManager;
    }
}
