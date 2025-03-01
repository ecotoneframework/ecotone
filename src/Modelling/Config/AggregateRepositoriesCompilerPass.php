<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Config;

use function array_diff;
use function array_keys;
use function class_exists;

use Ecotone\Messaging\Config\Container\Compiler\CompilerPass;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Modelling\Repository\AggregateRepositoryBuilder;
use Ecotone\Modelling\Repository\AllAggregateRepository;

use function implode;

use InvalidArgumentException;

class AggregateRepositoriesCompilerPass implements CompilerPass
{
    /**
     * @param array<class-string, string> $aggregateRepositoryReferenceNames
     * @param array<AggregateRepositoryBuilder> $aggregateRepositoryBuilders
     */
    public function __construct(
        private array $aggregateRepositoryReferenceNames,
        private array $aggregateRepositoryBuilders,
    ) {
    }

    public function process(ContainerBuilder $builder): void
    {
        $aggregateRepositories = [];
        $repositoriesLeftToHandle = array_keys($this->aggregateRepositoryReferenceNames);

        foreach ($this->aggregateRepositoryBuilders as $aggregateRepositoryBuilder) {
            $aggregateRepositoriesForThisBuilder = [];
            foreach ($this->aggregateRepositoryReferenceNames as $classNameOrReferenceId => $referenceId) {
                if (! class_exists($classNameOrReferenceId)) {
                    if ($builder->has($referenceId)) {
                        $className = $builder->getDefinition($referenceId)->getClassName();
                    } else {
                        throw new InvalidArgumentException("Class $classNameOrReferenceId does not exist and there is no service registered under $referenceId");
                    }
                } else {
                    $className = $classNameOrReferenceId;
                }
                if ($aggregateRepositoryBuilder->canHandle($className)) {
                    $aggregateRepositoriesForThisBuilder[] = $referenceId;
                    $repositoriesLeftToHandle = array_diff($repositoriesLeftToHandle, [$classNameOrReferenceId]);
                }
            }
            foreach ($aggregateRepositoriesForThisBuilder as $referenceId) {
                $aggregateRepositories[] = $aggregateRepositoryBuilder->compile($referenceId, count($aggregateRepositoriesForThisBuilder) === 1);
            }
        }

        if (! empty($repositoriesLeftToHandle)) {
            throw new InvalidArgumentException('No aggregate repository builder found for ' . implode(', ', $repositoriesLeftToHandle) . '. Did you forget to implement one of StandardRepository or EventSourcedRepository interface ?');
        }

        $builder->register(AllAggregateRepository::class, new Definition(AllAggregateRepository::class, [$aggregateRepositories]));
    }
}
