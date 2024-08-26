<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\ResolveAggregate;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\EventSourcingExecutor\EventSourcingHandlerExecutorBuilder;

/**
 * licence Apache-2.0
 */
final class ResolveAggregateServiceBuilder implements CompilableBuilder
{
    private function __construct(
        private ClassDefinition $aggregateClassDefinition,
        private ?ClassDefinition $resultClassDefinition,
        private InterfaceToCallRegistry $interfaceToCallRegistry,
        private InterfaceToCall $interfaceToCall,
        private bool $isCalledAggregateEventSourced,
        private bool $isReturningAggregate,
        private ?bool $isFactoryMethod,
        private bool $isResultAggregateEventSourced,
    ) {
    }

    public static function create(ClassDefinition $aggregateClassDefinition, string $methodName, InterfaceToCallRegistry $interfaceToCallRegistry): self
    {
        $interfaceToCall = $interfaceToCallRegistry->getFor($aggregateClassDefinition->getClassType()->toString(), $methodName);
        $isCalledAggregateEventSourced = $aggregateClassDefinition->hasClassAnnotation(TypeDescriptor::create(EventSourcingAggregate::class));
        $isReturningAggregate = $interfaceToCall->isReturningAggregate($interfaceToCallRegistry);
        if ($isReturningAggregate) {
            $resultClassDefinition = ClassDefinition::createFor($interfaceToCall->getReturnType());
            $isResultAggregateEventSourced = $resultClassDefinition->hasClassAnnotation(TypeDescriptor::create(EventSourcingAggregate::class));
        }
        $isFactoryMethod = $interfaceToCall->isFactoryMethod();
        return new self(
            $aggregateClassDefinition,
            $resultClassDefinition ?? null,
            $interfaceToCallRegistry,
            $interfaceToCall,
            $isCalledAggregateEventSourced,
            $isReturningAggregate,
            $isFactoryMethod,
            $isResultAggregateEventSourced ?? false
        );
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        if ($this->isFactoryMethod) {
            if ($this->isCalledAggregateEventSourced) {
                return $this->resolveEventSourcingAggregateService(true, $this->aggregateClassDefinition);
            }
            return $this->resolveStateBasedAggregateService(true);
        }
        if ($this->isReturningAggregate) {
            return $this->resolveMultipleAggregatesService();
        } elseif ($this->isCalledAggregateEventSourced) {
            return $this->resolveEventSourcingAggregateService(false, $this->aggregateClassDefinition);
        } else {
            return $this->resolveStateBasedAggregateService(false);
        }
    }

    private function resolveMultipleAggregatesService(): Definition
    {
        if ($this->isCalledAggregateEventSourced) {
            $resolveCalledAggregateEventsService = $this->resolveEventSourcingAggregateService(false, $this->aggregateClassDefinition);
        } else {
            $resolveCalledAggregateEventsService = $this->resolveStateBasedAggregateService(false);
        }

        if ($this->isResultAggregateEventSourced) {
            $resolveResultAggregateEventsService = $this->resolveEventSourcingAggregateService(true, $this->resultClassDefinition);
        } else {
            $resolveResultAggregateEventsService = $this->resolveStateBasedAggregateService(true);
        }

        return new Definition(ResolveMultipleAggregatesService::class, [
            $resolveCalledAggregateEventsService,
            $resolveResultAggregateEventsService,
        ]);
    }

    private function resolveEventSourcingAggregateService(bool $isFactoryMethod, ClassDefinition $classDefinition): Definition
    {
        return new Definition(ResolveEventSourcingAggregateService::class, [
            $isFactoryMethod,
            EventSourcingHandlerExecutorBuilder::createFor($classDefinition, true, $this->interfaceToCallRegistry),
            $this->interfaceToCall->getInterfaceName(),
        ]);
    }

    private function resolveStateBasedAggregateService(bool $isFactoryMethod): Definition
    {
        return new Definition(ResolveStateBasedAggregateService::class, [
            $isFactoryMethod,
        ]);
    }
}
