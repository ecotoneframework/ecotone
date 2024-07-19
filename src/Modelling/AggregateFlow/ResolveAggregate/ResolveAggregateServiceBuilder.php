<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\ResolveAggregate;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\EventSourcingHandlerExecutor;

/**
 * licence Apache-2.0
 */
final class ResolveAggregateServiceBuilder extends InputOutputMessageHandlerBuilder
{
    private ClassDefinition $aggregateClassDefinition;
    private ?ClassDefinition $resultClassDefinition = null;
    private InterfaceToCallRegistry $interfaceToCallRegistry;
    private InterfaceToCall $interfaceToCall;
    private bool $isCalledAggregateEventSourced = false;
    private bool $isReturningAggregate = false;
    private ?bool $isFactoryMethod = false;
    private $isResultAggregateEventSourced = false;

    private function __construct(ClassDefinition $aggregateClassDefinition, string $methodName, InterfaceToCallRegistry $interfaceToCallRegistry)
    {
        $this->initialize($aggregateClassDefinition, $methodName, $interfaceToCallRegistry);
    }

    public static function create(ClassDefinition $aggregateClassDefinition, string $methodName, InterfaceToCallRegistry $interfaceToCallRegistry): self
    {
        return new self($aggregateClassDefinition, $methodName, $interfaceToCallRegistry);
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        if ($this->isFactoryMethod) {
            if ($this->isCalledAggregateEventSourced) {
                return ServiceActivatorBuilder::createWithDefinition(definition: $this->resolveEventSourcingAggregateService(true, $this->aggregateClassDefinition), methodName: 'resolve')
                    ->withOutputMessageChannel($this->outputMessageChannelName)
                    ->compile($builder)
                ;
            }
            return ServiceActivatorBuilder::createWithDefinition(definition: $this->resolveStateBasedAggregateService(true), methodName: 'resolve')
                ->withOutputMessageChannel($this->outputMessageChannelName)
                ->compile($builder)
            ;
        }
        if ($this->isReturningAggregate) {
            $resolveAggregateEventsService = $this->resolveMultipleAggregatesService();
        } elseif ($this->isCalledAggregateEventSourced) {
            $resolveAggregateEventsService = $this->resolveEventSourcingAggregateService(false, $this->aggregateClassDefinition);
        } else {
            $resolveAggregateEventsService = $this->resolveStateBasedAggregateService(false);
        }

        return ServiceActivatorBuilder::createWithDefinition(definition: $resolveAggregateEventsService, methodName: 'resolve')
            ->withOutputMessageChannel($this->outputMessageChannelName)
            ->compile($builder)
        ;
    }

    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(ResolveEventSourcingAggregateService::class, 'resolve');
    }

    private function initialize(ClassDefinition $aggregateClassDefinition, string $methodName, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $this->aggregateClassDefinition = $aggregateClassDefinition;
        $this->interfaceToCallRegistry = $interfaceToCallRegistry;
        $this->interfaceToCall = $this->interfaceToCallRegistry->getFor($this->aggregateClassDefinition->getClassType()->toString(), $methodName);
        $this->isCalledAggregateEventSourced = $this->aggregateClassDefinition->hasClassAnnotation(TypeDescriptor::create(EventSourcingAggregate::class));
        $this->isReturningAggregate = $this->interfaceToCall->isReturningAggregate($interfaceToCallRegistry);
        if ($this->isReturningAggregate) {
            $this->resultClassDefinition = ClassDefinition::createFor($this->interfaceToCall->getReturnType());
            $this->isResultAggregateEventSourced = $this->resultClassDefinition->hasClassAnnotation(TypeDescriptor::create(EventSourcingAggregate::class));
        }
        $this->isFactoryMethod = $this->interfaceToCall->isFactoryMethod();
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
            EventSourcingHandlerExecutor::createFor($classDefinition, true, $this->interfaceToCallRegistry),
        ]);
    }

    private function resolveStateBasedAggregateService(bool $isFactoryMethod): Definition
    {
        return new Definition(ResolveStateBasedAggregateService::class, [
            $isFactoryMethod,
        ]);
    }
}
