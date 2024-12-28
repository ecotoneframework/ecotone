<?php

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateResolver;
use Ecotone\Modelling\BaseEventSourcingConfiguration;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\LazyEventSourcedRepository;
use Ecotone\Modelling\LazyStandardRepository;
use Psr\Container\ContainerInterface;

/**
 * Class AggregateCallingCommandHandlerBuilder
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class SaveAggregateServiceBuilder implements CompilableBuilder
{
    /**
     * @var string[]
     */
    private array $aggregateRepositoryReferenceNames = [];

    private ?string $calledAggregateClassName = null;

    private function __construct(
        private BaseEventSourcingConfiguration $eventSourcingConfiguration,
        private bool $publishEvents = true,
    ) {
    }

    /**
     * @param string[] $aggregateClasses
     */
    public static function create(
        BaseEventSourcingConfiguration $eventSourcingConfiguration,
    ): self {
        return new self($eventSourcingConfiguration);
    }

    /**
     * @param string[] $aggregateRepositoryReferenceNames
     */
    public function withAggregateRepositoryFactories(array $aggregateRepositoryReferenceNames): self
    {
        $this->aggregateRepositoryReferenceNames = $aggregateRepositoryReferenceNames;

        return $this;
    }

    public function withPublishEvents(bool $publishEvents): self
    {
        $this->publishEvents = $publishEvents;

        return $this;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        $eventSourcedRepository = new Definition(LazyEventSourcedRepository::class, [
            array_map(static fn ($id) => new Reference($id), $this->aggregateRepositoryReferenceNames),
        ], 'create');

        $standardRepository = new Definition(LazyStandardRepository::class, [
            array_map(static fn ($id) => new Reference($id), $this->aggregateRepositoryReferenceNames),
        ], 'create');

        return new Definition(SaveAggregateService::class, [
            $eventSourcedRepository,
            Definition::createFor(PropertyReaderAccessor::class, []),
            $standardRepository,
            new Reference(AggregateResolver::class),
            $this->eventSourcingConfiguration,
            $this->publishEvents,
            Reference::to(EventBus::class),
            Reference::to(ContainerInterface::class),
        ]);
    }

    public function __toString()
    {
        return sprintf('Save Aggregate Processor - %s', $this->calledAggregateClassName);
    }
}
