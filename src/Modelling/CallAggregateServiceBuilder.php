<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Attribute\AggregateEvents;
use Ecotone\Modelling\Attribute\AggregateFactory;
use Ecotone\Modelling\Attribute\AggregateVersion;
use Ecotone\Modelling\Attribute\EventSourcedAggregate;

class CallAggregateServiceBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters, MessageHandlerBuilderWithOutputChannel
{
    private InterfaceToCall $interfaceToCall;
    /**
     * @var ParameterConverterBuilder[]
     */
    private array $methodParameterConverterBuilders = [];
    /**
     * @var string[]
     */
    private array $requiredReferences = [];
    /**
     * @var bool
     */
    private bool $isCommandHandler;
    /**
     * @var string[]
     */
    private array $aggregateRepositoryReferenceNames = [];
    private bool $isVoidMethod;
    private ?string $eventSourcedFactoryMethod;
    private ?string $aggregateMethodWithEvents;
    private ?string $aggregateVersionProperty;
    private bool $isAggregateVersionAutomaticallyIncreased = true;
    private bool $isEventSourced = false;

    private function __construct(ClassDefinition $aggregateClassDefinition, string $methodName, bool $isCommandHandler)
    {
        $this->isCommandHandler = $isCommandHandler;

        $this->initialize($aggregateClassDefinition, $methodName);
    }

    private function initialize(ClassDefinition $aggregateClassDefinition, string $methodName): void
    {
        $interfaceToCall = InterfaceToCall::create($aggregateClassDefinition->getClassType()->toString(), $methodName);
        $this->isVoidMethod = $interfaceToCall->getReturnType()->isVoid();

        $eventSourcedFactoryMethod = null;
        $aggregateFactoryAnnotation = TypeDescriptor::create(AggregateFactory::class);
        foreach ($aggregateClassDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = InterfaceToCall::create($aggregateClassDefinition->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateFactoryAnnotation)) {
                $eventSourcedFactoryMethod = $method;
                break;
            }
        }
        $this->eventSourcedFactoryMethod = $eventSourcedFactoryMethod;

        $aggregateMethodWithEvents    = null;
        $aggregateEventsAnnotation = TypeDescriptor::create(AggregateEvents::class);
        foreach ($aggregateClassDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = InterfaceToCall::create($aggregateClassDefinition->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateEventsAnnotation)) {
                $aggregateMethodWithEvents = $method;
                break;
            }
        }
        $this->aggregateMethodWithEvents = $aggregateMethodWithEvents;

        $hasInternalEventRecorder = null;
        $eventSourcedAggregateAnnotation = TypeDescriptor::create(EventSourcedAggregate::class);
        if ($interfaceToCall->hasClassAnnotation($eventSourcedAggregateAnnotation)) {
            $this->isEventSourced = true;
            /** @var EventSourcedAggregate $annotation */
            $annotation = $interfaceToCall->getClassAnnotation($eventSourcedAggregateAnnotation);
            $hasInternalEventRecorder = $annotation->hasInternalEventRecorder();

            if ($hasInternalEventRecorder) {
                Assert::isTrue($aggregateMethodWithEvents, "{$interfaceToCall} has defined to use internal event recorder. Method with attribute " . AggregateEvents::class . " must be defined.");
            }else {
                Assert::isTrue(!$aggregateMethodWithEvents, "{$interfaceToCall} has defined " . AggregateEvents::class . " attribute for a method. However aggregate has not defined internal event recorder. You may change it in " . EventSourcedAggregate::class . " attribute.");
            }
            Assert::notNull($this->eventSourcedFactoryMethod, "Event Sourced Aggregate must have method annotated with " . AggregateFactory::class . " in order to rebuild the aggregate.");
        }

        $aggregateVersionPropertyName = null;
        $versionAnnotation             = TypeDescriptor::create(AggregateVersion::class);
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($versionAnnotation)) {
                $aggregateVersionPropertyName = $property->getName();
                /** @var AggregateVersion $annotation */
                $annotation = $property->getAnnotation($versionAnnotation);
                $this->isAggregateVersionAutomaticallyIncreased = $annotation->isAutoIncreased();
            }
        }
        $this->aggregateVersionProperty             = $aggregateVersionPropertyName;

        if ($this->isEventSourced) {
            Assert::isTrue((bool)$this->aggregateVersionProperty, "{$interfaceToCall->getInterfaceName()} is event sourced aggregate. Event Sourced aggregates are required to define version property. Make use of " . WithAggregateVersioning::class . " or implement your own.");
        }

        $this->interfaceToCall = $interfaceToCall;
    }

    public static function create(ClassDefinition $aggregateClassDefinition, string $methodName, bool $isCommandHandler): self
    {
        return new self($aggregateClassDefinition, $methodName, $isCommandHandler);
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
    public function getRequiredReferenceNames(): array
    {
        return $this->requiredReferences;
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

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $isFactoryMethod = $this->interfaceToCall->isStaticallyCalled();
        if (!$this->isEventSourced && $isFactoryMethod) {
            Assert::isTrue($this->interfaceToCall->getReturnType()->isClassNotInterface(), "Factory method {$this->interfaceToCall} for standard aggregate should return object. Did you wanted to register Event Sourced Aggregate?");
        }

        $handler = ServiceActivatorBuilder::createWithDirectReference(
            new CallAggregateService($this->interfaceToCall, $this->isEventSourced, $channelResolver, $this->methodParameterConverterBuilders, $this->orderedAroundInterceptors, $referenceSearchService, new PropertyReaderAccessor(), PropertyEditorAccessor::create($referenceSearchService), $this->isCommandHandler, $isFactoryMethod, $this->eventSourcedFactoryMethod, $this->aggregateVersionProperty, $this->isAggregateVersionAutomaticallyIncreased, $this->aggregateMethodWithEvents),
            "call"
        )
            ->withPassThroughMessageOnVoidInterface($this->isVoidMethod)
            ->withOutputMessageChannel($this->outputMessageChannelName);

        return $handler->build($channelResolver, $referenceSearchService);
    }

    /**
     * @param string[] $aggregateRepositoryReferenceNames
     */
    public function withAggregateRepositoryFactories(array $aggregateRepositoryReferenceNames): self
    {
        $this->aggregateRepositoryReferenceNames = $aggregateRepositoryReferenceNames;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [
            $interfaceToCallRegistry->getFor($this->interfaceToCall->getInterfaceName(), $this->interfaceToCall->getMethodName()),
            $interfaceToCallRegistry->getFor(CallAggregateService::class, "call")
        ];
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor($this->interfaceToCall->getInterfaceName(), $this->interfaceToCall->getMethodName());
    }

    public function __toString()
    {
        return sprintf("Aggregate Handler - %s with name `%s` for input channel `%s`", (string)$this->interfaceToCall, $this->getEndpointId(), $this->getInputMessageChannelName());
    }
}