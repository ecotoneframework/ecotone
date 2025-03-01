<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\EventSourcing;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\BaseEventSourcingConfiguration;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\EventSourcingExecutor\GroupedEventSourcingExecutor;
use Ecotone\Modelling\Repository\AggregateRepositoryBuilder;

use function is_a;

use Psr\Container\ContainerInterface;

class EventSourcedRepositoryAdapterBuilder implements AggregateRepositoryBuilder
{
    public function __construct(private BaseEventSourcingConfiguration $baseEventSourcingConfiguration)
    {
    }

    public function canHandle(string $repositoryClassName): bool
    {
        return is_a($repositoryClassName, EventSourcedRepository::class, true);
    }

    public function compile(string $referenceId, bool $isDefault): Definition|Reference
    {
        return new Definition(EventSourcedRepositoryAdapter::class, [
            new Reference($referenceId),
            new Reference(AggregateDefinitionRegistry::class),
            $this->baseEventSourcingConfiguration,
            new Reference(GroupedEventSourcingExecutor::class),
            new Reference(ContainerInterface::class),
            new Reference(PropertyEditorAccessor::class),
            $isDefault,
            new Reference(LoggingGateway::class),
        ]);
    }
}
