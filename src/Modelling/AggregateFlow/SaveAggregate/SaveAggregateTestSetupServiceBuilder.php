<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\EventSourcing\Mapping\EventMapper;
use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\Repository\AllAggregateRepository;

/**
 * licence Apache-2.0
 */
final class SaveAggregateTestSetupServiceBuilder implements CompilableBuilder
{
    public static function create(): self
    {
        return new self();
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(SaveAggregateTestSetupService::class, [
            new Reference(AggregateDefinitionRegistry::class),
            new Reference(PropertyReaderAccessor::class),
            new Reference(ConversionService::class),
            DefaultHeaderMapper::createAllHeadersMapping()->getDefinition(),
            Reference::to(EventMapper::class),
            new Reference(AllAggregateRepository::class),
        ]);
    }

    public function __toString(): string
    {
        return sprintf('Save Aggregate Test Setup State Processor');
    }
}
