<?php declare(strict_types=1);

namespace Ecotone\Dbal\ObjectManager;

use Doctrine\Persistence\ManagerRegistry;
use Ecotone\Modelling\StandardRepository;

class ManagerRegistryRepository implements StandardRepository
{
    public function __construct(private ManagerRegistry $managerRegistry, private ?array $relatedClasses) {}

    public function canHandle(string $aggregateClassName): bool
    {
        if (is_null($this->relatedClasses)) {
            return true;
        }

        return in_array($aggregateClassName, $this->relatedClasses);
    }

    public function findBy(string $aggregateClassName, array $identifiers): ?object
    {
        return $this->managerRegistry->getRepository($aggregateClassName)->findOneBy($identifiers);
    }

    public function save(array $identifiers, object $aggregate, array $metadata, ?int $versionBeforeHandling): void
    {
        $objectManager = $this->managerRegistry->getManagerForClass(get_class($aggregate));

        $objectManager->persist($aggregate);
        $objectManager->flush();
    }
}