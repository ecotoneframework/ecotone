<?php

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateResolver;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\Repository\AllAggregateRepository;

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
    private ?string $calledAggregateClassName = null;

    public static function create(): self
    {
        return new self();
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(SaveAggregateService::class, [
            new Reference(AllAggregateRepository::class),
            Definition::createFor(PropertyReaderAccessor::class, []),
            new Reference(AggregateResolver::class),
            Reference::to(EventBus::class),
        ]);
    }

    public function __toString()
    {
        return sprintf('Save Aggregate Processor - %s', $this->calledAggregateClassName);
    }
}
