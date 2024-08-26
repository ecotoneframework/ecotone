<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\PublishEvents;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Modelling\EventBus;

/**
 * licence Apache-2.0
 */
final class PublishAggregateEventsServiceBuilder implements CompilableBuilder
{
    private function __construct(private InterfaceToCallReference $interfaceToCallReference)
    {
    }

    public static function create(ClassDefinition $aggregateClassDefinition, string $methodName): self
    {
        return new self(new InterfaceToCallReference($aggregateClassDefinition->getClassType()->toString(), $methodName));
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        return new Definition(PublishAggregateEventsService::class, [
            $this->interfaceToCallReference->getName(),
            new Reference(EventBus::class),
        ]);
    }
}
