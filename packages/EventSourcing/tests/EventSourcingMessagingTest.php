<?php


namespace Test\Ecotone\EventSourcing;


use Ecotone\Dbal\DbalReconnectableConnectionFactory;
use Ecotone\EventSourcing\EventSourcingRepository;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use Interop\Queue\ConnectionFactory;
use PHPUnit\Framework\TestCase;

abstract class EventSourcingMessagingTest extends TestCase
{
    /**
     * @var DbalConnectionFactory|ManagerRegistryConnectionFactory
     */
    private $dbalConnectionFactory;

    protected function setUp(): void
    {
        $this->getConnectionFactory()->createContext()->getDbalConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        try {
            $this->getConnectionFactory()->createContext()->getDbalConnection()->rollBack();
        }catch (\Exception) {}
    }

    public function getConnectionFactory(bool $isRegistry = false) : ConnectionFactory
    {
        if (!$this->dbalConnectionFactory) {
            $dsn = getenv("DATABASE_DSN") ? getenv("DATABASE_DSN") : null;
            if (!$dsn) {
                throw new \InvalidArgumentException("Missing env `DATABASE_DSN` pointing to test database");
            }
            $dbalConnectionFactory = new DbalConnectionFactory($dsn);
            $this->dbalConnectionFactory = $isRegistry
                ? new ManagerRegistryConnectionFactory(
                    new DbalConnectionManagerRegistryWrapper($dbalConnectionFactory)
                )
                : $dbalConnectionFactory;
        }

        return $this->dbalConnectionFactory;
    }

    protected function getReferenceSearchServiceWithConnection(array $objects = [], bool $connectionAsRegistry = false)
    {
        return InMemoryReferenceSearchService::createWith(
            array_merge(
                [DbalConnectionFactory::class => $this->getConnectionFactory($connectionAsRegistry)],
                $objects
            )
        );
    }
}