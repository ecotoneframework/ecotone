<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Store\Document\DocumentStore;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Attribute\AggregateEvents;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\AggregateIdentifierMethod;
use Ecotone\Modelling\Attribute\AggregateVersion;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;

/**
 * Class AggregateCallingCommandHandlerBuilder
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SaveAggregateServiceBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters, MessageHandlerBuilderWithOutputChannel
{
    private InterfaceToCall $interfaceToCall;
    /**
     * @var ParameterConverterBuilder[]
     */
    private array $methodParameterConverterBuilders = [];
    /**
     * @var string[]
     */
    private array $aggregateRepositoryReferenceNames = [];
    private array $aggregateIdentifierMapping;
    private array $aggregateIdentifierGetMethods = [];
    private ?string $aggregateVersionProperty;
    private bool $isAggregateVersionAutomaticallyIncreased = true;
    private ?string $aggregateMethodWithEvents;
    private bool $isEventSourced = false;

    private function __construct(
        ClassDefinition $aggregateClassDefinition,
        string $methodName,
        InterfaceToCallRegistry $interfaceToCallRegistry,
        private bool $useSnapshot = false,
        private int $snapshotTriggerThreshold = BaseEventSourcingConfiguration::DEFAULT_SNAPSHOT_TRIGGER_THRESHOLD,
        private string $documentStoreReference = DocumentStore::class
    ) {
        $this->initialize($aggregateClassDefinition, $methodName, $interfaceToCallRegistry);
    }

    public static function create(
        ClassDefinition $aggregateClassDefinition,
        string $methodName,
        InterfaceToCallRegistry $interfaceToCallRegistry,
        bool $useSnapshot,
        int $snapshotTriggerThreshold,
        string $documentStoreReference
    ): self {
        return new self($aggregateClassDefinition, $methodName, $interfaceToCallRegistry, $useSnapshot, $snapshotTriggerThreshold, $documentStoreReference);
    }

    /**
     * @inheritDoc
     */
    public function getParameterConverters(): array
    {
        return $this->methodParameterConverterBuilders;
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
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, ParameterConverterBuilder::class);

        $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;

        return $this;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        // TODO: Duplication with LoadAggregateServiceBuilder
        $repository = $this->isEventSourced
            ? new Definition(LazyEventSourcedRepository::class, [
                $this->interfaceToCall->getInterfaceName(),
                $this->isEventSourced,
                array_map(fn ($id) => new Reference($id), $this->aggregateRepositoryReferenceNames),
            ], 'create')
            : new Definition(LazyStandardRepository::class, [
                $this->interfaceToCall->getInterfaceName(),
                $this->isEventSourced,
                array_map(fn ($id) => new Reference($id), $this->aggregateRepositoryReferenceNames),
            ], 'create');


        $saveAggregateService = new Definition(SaveAggregateService::class, [
            InterfaceToCallReference::fromInstance($this->interfaceToCall),
            $this->interfaceToCall->isStaticallyCalled(),
            $this->isEventSourced,
            $repository,
            new Reference(PropertyEditorAccessor::class),
            new Reference(PropertyReaderAccessor::class),
            new Reference(EventBus::class),
            $this->aggregateMethodWithEvents,
            $this->aggregateIdentifierMapping,
            $this->aggregateIdentifierGetMethods,
            $this->aggregateVersionProperty,
            $this->isAggregateVersionAutomaticallyIncreased,
            $this->useSnapshot,
            $this->snapshotTriggerThreshold,
            $this->useSnapshot ? new Reference($this->documentStoreReference) : null,
        ]);

        return ServiceActivatorBuilder::createWithDefinition($saveAggregateService, 'save')
            ->withOutputMessageChannel($this->outputMessageChannelName)
            ->compile($builder);
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(SaveAggregateService::class, 'save');
    }

    public function __toString()
    {
        return sprintf('Aggregate Handler - %s with name `%s` for input channel `%s`', (string)$this->interfaceToCall, $this->getEndpointId(), $this->getInputMessageChannelName());
    }

    private function initialize(ClassDefinition $aggregateClassDefinition, string $methodName, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $interfaceToCall = $interfaceToCallRegistry->getFor($aggregateClassDefinition->getClassType()->toString(), $methodName);

        $aggregateMethodWithEvents    = null;
        $aggregateIdentifiers          = [];

        $aggregateEventsAnnotation = TypeDescriptor::create(AggregateEvents::class);
        $aggregateIdentifierGetMethodAttribute = TypeDescriptor::create(AggregateIdentifierMethod::class);
        $aggregateIdentifierGetMethods = [];

        foreach ($aggregateClassDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = $interfaceToCallRegistry->getFor($aggregateClassDefinition->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateEventsAnnotation)) {
                $aggregateMethodWithEvents = $method;
            }
            if ($methodToCheck->hasMethodAnnotation($aggregateIdentifierGetMethodAttribute)) {
                if (! $methodToCheck->hasNoParameters()) {
                    throw NoCorrectIdentifierDefinedException::create($interfaceToCall . ' should not have any parameters.');
                }

                /** @var AggregateIdentifierMethod $attribute */
                $attribute = $methodToCheck->getMethodAnnotation($aggregateIdentifierGetMethodAttribute);
                $aggregateIdentifiers[$attribute->getIdentifierPropertyName()] = null;
                $aggregateIdentifierGetMethods[$attribute->getIdentifierPropertyName()] = $method;
            }
        }

        $this->isEventSourced = $aggregateClassDefinition->hasClassAnnotation(TypeDescriptor::create(EventSourcingAggregate::class));

        $aggregateIdentifierAnnotation = TypeDescriptor::create(AggregateIdentifier::class);
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($aggregateIdentifierAnnotation)) {
                $aggregateIdentifiers[$property->getName()] = null;
            }
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
        $this->aggregateVersionProperty  = $aggregateVersionPropertyName;

        $this->interfaceToCall            = $interfaceToCall;
        $this->aggregateMethodWithEvents  = $aggregateMethodWithEvents;
        $this->aggregateIdentifierMapping = $aggregateIdentifiers;
        $this->aggregateIdentifierGetMethods = $aggregateIdentifierGetMethods;
    }
}
