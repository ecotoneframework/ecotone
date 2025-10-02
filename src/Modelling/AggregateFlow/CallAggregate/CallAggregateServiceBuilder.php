<?php

namespace Ecotone\Modelling\AggregateFlow\CallAggregate;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\InterceptedMessageProcessorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerAggregateObjectResolver;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Attribute\AggregateVersion;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingSaga;
use Ecotone\Modelling\WithAggregateVersioning;

/**
 * licence Apache-2.0
 */
class CallAggregateServiceBuilder implements InterceptedMessageProcessorBuilder
{
    private InterfaceToCall $interfaceToCall;
    /**
     * @var ParameterConverterBuilder[]
     */
    private array $methodParameterConverterBuilders = [];
    /**
     * @var bool
     */
    private bool $isCommandHandler;
    private ?string $aggregateVersionProperty;
    private bool $isEventSourced = false;

    private function __construct(ClassDefinition $aggregateClassDefinition, string $methodName, bool $isCommandHandler, InterfaceToCallRegistry $interfaceToCallRegistry)
    {
        $this->isCommandHandler = $isCommandHandler;

        $this->initialize($aggregateClassDefinition, $methodName, $interfaceToCallRegistry);
    }

    private function initialize(ClassDefinition $aggregateClassDefinition, string $methodName, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $interfaceToCall = $interfaceToCallRegistry->getFor($aggregateClassDefinition->getClassType()->toString(), $methodName);

        $eventSourcedAggregateAnnotation = Type::attribute(EventSourcingAggregate::class);
        $eventSourcedSagaAnnotation = Type::attribute(EventSourcingSaga::class);
        /** @var EventSourcingAggregate|null $eventSourcingAttribute */
        $eventSourcingAttribute = null;
        if ($interfaceToCall->hasClassAnnotation($eventSourcedAggregateAnnotation) || $interfaceToCall->hasClassAnnotation($eventSourcedSagaAnnotation)) {
            $this->isEventSourced = true;
            $eventSourcingAttribute = $interfaceToCall->getSingleClassAnnotationOf($eventSourcedAggregateAnnotation);
        }

        $aggregateVersionPropertyName = null;
        $versionAnnotation             = Type::attribute(AggregateVersion::class);
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($versionAnnotation)) {
                $aggregateVersionPropertyName = $property->getName();
                // TODO: should throw exception if more than one version property
            }
        }
        $this->aggregateVersionProperty             = $aggregateVersionPropertyName;

        if ($this->isEventSourced) {
            Assert::isTrue((bool)$this->aggregateVersionProperty, "{$interfaceToCall->getInterfaceName()} is event sourced aggregate. Event Sourced aggregates are required to define version property. Make use of " . WithAggregateVersioning::class . ' or implement your own.');
        }

        $this->interfaceToCall = $interfaceToCall;
        $isFactoryMethod = $this->interfaceToCall->isFactoryMethod();
        if ($isFactoryMethod) {
            if ($this->isEventSourced && ! $eventSourcingAttribute->hasInternalEventRecorder()) {
                return;
            }

            Assert::isTrue($this->interfaceToCall->getReturnType()->isClassNotInterface(), "Factory method {$this->interfaceToCall} for standard aggregate should return object. Did you wanted to register Event Sourced Aggregate?");
        }
    }

    public static function create(ClassDefinition $aggregateClassDefinition, string $methodName, bool $isCommandHandler, InterfaceToCallRegistry $interfaceToCallRegistry): self
    {
        return new self($aggregateClassDefinition, $methodName, $isCommandHandler, $interfaceToCallRegistry);
    }

    /**
     * @inheritDoc
     */
    public function getParameterConverters(): array
    {
        return $this->methodParameterConverterBuilders;
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, ParameterConverterBuilder::class);

        $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;

        return $this;
    }

    public function compile(MessagingContainerBuilder $builder, array $aroundInterceptors = []): Definition
    {
        return MethodInvokerBuilder::create(
            $this->interfaceToCall->isStaticallyCalled()
                ? $this->interfaceToCall->getInterfaceName()
                : new Definition(MethodInvokerAggregateObjectResolver::class),
            InterfaceToCallReference::fromInstance($this->interfaceToCall),
            $this->methodParameterConverterBuilders
        )
            ->withResultToMessageConverter(
                new Definition(CallAggregateResultToMessageConverter::class, [
                    $this->interfaceToCall->getReturnType(),
                    new Reference(PropertyReaderAccessor::class),
                    $this->isCommandHandler,
                    $this->interfaceToCall->isFactoryMethod() ?? false,
                    $this->aggregateVersionProperty,
                ])
            )
            ->compile($builder, $aroundInterceptors);
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(): InterfaceToCallReference
    {
        return InterfaceToCallReference::fromInstance($this->interfaceToCall);
    }

    public function __toString()
    {
        return sprintf('Call Aggregate Handler - %s', (string)$this->interfaceToCall);
    }
}
