<?php

declare(strict_types=1);

namespace Ecotone\Dbal\ObjectManager;

use Doctrine\Persistence\ManagerRegistry;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\RepositoryBuilder;
use Ecotone\Modelling\StandardRepository;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use ReflectionClass;

class DoctrineORMRepositoryBuilder implements RepositoryBuilder
{
    public function __construct(private string $connectionReferenceName, private ?array $relatedClasses)
    {
    }

    public function canHandle(string $aggregateClassName): bool
    {
        if (is_null($this->relatedClasses)) {
            return true;
        }

        return in_array($aggregateClassName, $this->relatedClasses);
    }

    public function isEventSourced(): bool
    {
        return false;
    }

    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): EventSourcedRepository|StandardRepository
    {
        /** @var ManagerRegistryConnectionFactory $connectionFactory */
        $connectionFactory = $referenceSearchService->get($this->connectionReferenceName);

        $registry = new ReflectionClass($connectionFactory);
        $property = $registry->getProperty('registry');
        $property->setAccessible(true);
        /** @var ManagerRegistry $registry */
        $registry = $property->getValue($connectionFactory);

        return new ManagerRegistryRepository($registry, $this->relatedClasses);
    }
}
