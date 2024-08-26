<?php

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\AggregateIdentifierMethod;
use Ecotone\Modelling\Attribute\AggregateVersion;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\BaseEventSourcingConfiguration;
use Ecotone\Modelling\LazyEventSourcedRepository;
use Ecotone\Modelling\LazyStandardRepository;
use Ecotone\Modelling\NoCorrectIdentifierDefinedException;

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
    private InterfaceToCall $interfaceToCall;
    /**
     * @var string[]
     */
    private array $aggregateRepositoryReferenceNames = [];

    private ?string $calledAggregateClassName = null;
    private array $calledAggregateIdentifierMapping = [];
    private array $calledAggregateIdentifierGetMethods = [];
    private ?string $calledAggregateVersionProperty = null;
    private bool $isCalledAggregateVersionAutomaticallyIncreased = true;
    private bool $isCalledAggregateEventSourced = false;

    private bool $isReturningAggregate = false;
    private ?string $resultAggregateClassName = null;
    private array $resultAggregateIdentifierMapping = [];
    private array $resultAggregateIdentifierGetMethods = [];
    private ?string $resultAggregateVersionProperty = null;
    private bool $isResultAggregateVersionAutomaticallyIncreased = true;
    private bool $isResultAggregateEventSourced = false;

    private function __construct(
        ClassDefinition $aggregateClassDefinition,
        string $methodName,
        InterfaceToCallRegistry $interfaceToCallRegistry,
        private BaseEventSourcingConfiguration $eventSourcingConfiguration,
    ) {
        $this->initialize($aggregateClassDefinition, $methodName, $interfaceToCallRegistry);
    }

    public static function create(
        ClassDefinition $aggregateClassDefinition,
        string $methodName,
        InterfaceToCallRegistry $interfaceToCallRegistry,
        BaseEventSourcingConfiguration $eventSourcingConfiguration,
    ): self {
        return new self($aggregateClassDefinition, $methodName, $interfaceToCallRegistry, $eventSourcingConfiguration);
    }

    /**
     * @param string[] $aggregateRepositoryReferenceNames
     */
    public function withAggregateRepositoryFactories(array $aggregateRepositoryReferenceNames): self
    {
        $this->aggregateRepositoryReferenceNames = $aggregateRepositoryReferenceNames;

        return $this;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        if ($this->isReturningAggregate) {
            return $this->saveMultipleAggregatesService();
        } elseif ($this->isCalledAggregateEventSourced) {
            return $this->saveEventSourcingAggregateService(
                $this->calledAggregateClassName,
                $this->interfaceToCall->isFactoryMethod(),
                $this->calledAggregateIdentifierMapping,
                $this->calledAggregateIdentifierGetMethods,
                $this->calledAggregateVersionProperty,
                $this->isCalledAggregateVersionAutomaticallyIncreased
            );
        } else {
            return $this->saveStateBasedAggregateService(
                $this->calledAggregateClassName,
                $this->interfaceToCall->isFactoryMethod(),
                $this->calledAggregateIdentifierMapping,
                $this->calledAggregateIdentifierGetMethods,
                $this->calledAggregateVersionProperty,
                $this->isCalledAggregateVersionAutomaticallyIncreased
            );
        }
    }

    public function __toString()
    {
        return sprintf('Save Aggregate Processor - %s', $this->calledAggregateClassName);
    }

    private function initialize(ClassDefinition $aggregateClassDefinition, string $methodName, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $this->isCalledAggregateEventSourced = $aggregateClassDefinition->hasClassAnnotation(TypeDescriptor::create(EventSourcingAggregate::class));
        $this->calledAggregateClassName = $aggregateClassDefinition->getClassType()->toString();

        [$this->calledAggregateIdentifierMapping, $this->calledAggregateIdentifierGetMethods] = $this->resolveAggregateIdentifierMapping($aggregateClassDefinition, $interfaceToCallRegistry);
        [$this->calledAggregateVersionProperty, $this->isCalledAggregateVersionAutomaticallyIncreased]  = $this->resolveAggregateVersionProperty($aggregateClassDefinition);

        $interfaceToCall = $interfaceToCallRegistry->getFor($this->calledAggregateClassName, $methodName);
        if ($interfaceToCall->getReturnType()?->isClassNotInterface()) {
            $returnTypeInterface = $interfaceToCallRegistry->getClassDefinitionFor(TypeDescriptor::create($interfaceToCall->getReturnType()->toString()));
            if (
                $returnTypeInterface->hasClassAnnotation(TypeDescriptor::create(Aggregate::class))
                && $returnTypeInterface->getClassType() !== $aggregateClassDefinition->getClassType()
            ) {
                $resultClassDefinition = ClassDefinition::createFor($interfaceToCall->getReturnType());
                $this->isReturningAggregate = true;
                $this->resultAggregateClassName = $interfaceToCall->getReturnType()?->toString();
                $this->isResultAggregateEventSourced = $resultClassDefinition->hasClassAnnotation(TypeDescriptor::create(EventSourcingAggregate::class));

                [$this->resultAggregateIdentifierMapping, $this->resultAggregateIdentifierGetMethods] = $this->resolveAggregateIdentifierMapping($returnTypeInterface, $interfaceToCallRegistry);
                [$this->resultAggregateVersionProperty, $this->isResultAggregateVersionAutomaticallyIncreased]  = $this->resolveAggregateVersionProperty($returnTypeInterface);
            }
        }


        $aggregateIdentifierAnnotation = TypeDescriptor::create(Identifier::class);
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($aggregateIdentifierAnnotation)) {
                $aggregateIdentifiers[$property->getName()] = null;
            }
        }

        $this->interfaceToCall = $interfaceToCall;
    }

    private function saveMultipleAggregatesService(): Definition
    {
        if ($this->isCalledAggregateEventSourced) {
            $saveCalledAggregateService = $this->saveEventSourcingAggregateService(
                $this->calledAggregateClassName,
                false,
                $this->calledAggregateIdentifierMapping,
                $this->calledAggregateIdentifierGetMethods,
                $this->calledAggregateVersionProperty,
                $this->isCalledAggregateVersionAutomaticallyIncreased,
            );
        } else {
            $saveCalledAggregateService = $this->saveStateBasedAggregateService(
                $this->calledAggregateClassName,
                false,
                $this->calledAggregateIdentifierMapping,
                $this->calledAggregateIdentifierGetMethods,
                $this->calledAggregateVersionProperty,
                $this->isCalledAggregateVersionAutomaticallyIncreased,
            );
        }

        if ($this->isResultAggregateEventSourced) {
            $saveResultAggregateService = $this->saveEventSourcingAggregateService(
                $this->resultAggregateClassName,
                true,
                $this->resultAggregateIdentifierMapping,
                $this->resultAggregateIdentifierGetMethods,
                $this->resultAggregateVersionProperty,
                $this->isResultAggregateVersionAutomaticallyIncreased,
            );
        } else {
            $saveResultAggregateService = $this->saveStateBasedAggregateService(
                $this->resultAggregateClassName,
                true,
                $this->resultAggregateIdentifierMapping,
                $this->resultAggregateIdentifierGetMethods,
                $this->resultAggregateVersionProperty,
                $this->isResultAggregateVersionAutomaticallyIncreased,
            );
        }

        return new Definition(SaveMultipleAggregateService::class, [
            $saveCalledAggregateService,
            $saveResultAggregateService,
        ]);
    }

    private function saveEventSourcingAggregateService(string $aggregateClassName, bool $isFactoryMethod, array $aggregateIdentifierMapping, array $aggregateIdentifierGetMethods, ?string $aggregateVersionProperty, bool $isAggregateVersionAutomaticallyIncreased): Definition
    {
        $repository = new Definition(LazyEventSourcedRepository::class, [
            $aggregateClassName,
            true,
            array_map(static fn ($id) => new Reference($id), $this->aggregateRepositoryReferenceNames),
        ], 'create');

        $useSnapshot = $this->eventSourcingConfiguration->useSnapshotFor($aggregateClassName);

        return new Definition(SaveEventSourcingAggregateService::class, [
            $this->interfaceToCall->toString(),
            $aggregateClassName,
            $isFactoryMethod,
            $repository,
            new Reference(PropertyEditorAccessor::class),
            new Reference(PropertyReaderAccessor::class),
            $aggregateIdentifierMapping,
            $aggregateIdentifierGetMethods,
            $aggregateVersionProperty,
            $isAggregateVersionAutomaticallyIncreased,
            $useSnapshot,
            $this->eventSourcingConfiguration->getSnapshotTriggerThresholdFor($aggregateClassName),
            $useSnapshot ? new Reference($this->eventSourcingConfiguration->getDocumentStoreReferenceFor($aggregateClassName)) : null,
        ]);
    }

    private function saveStateBasedAggregateService(string $aggregateClassName, bool $isFactoryMethod, array $aggregateIdentifierMapping, array $aggregateIdentifierGetMethods, ?string $aggregateVersionProperty, bool $isAggregateVersionAutomaticallyIncreased): Definition
    {
        $repository = new Definition(LazyStandardRepository::class, [
            $aggregateClassName,
            false,
            array_map(static fn ($id) => new Reference($id), $this->aggregateRepositoryReferenceNames),
        ], 'create');

        return new Definition(SaveStateBasedAggregateService::class, [
            $this->interfaceToCall->toString(),
            $isFactoryMethod,
            $repository,
            new Reference(PropertyEditorAccessor::class),
            new Reference(PropertyReaderAccessor::class),
            $aggregateIdentifierMapping,
            $aggregateIdentifierGetMethods,
            $aggregateVersionProperty,
            $isAggregateVersionAutomaticallyIncreased,
        ]);
    }

    private function resolveAggregateIdentifierMapping(ClassDefinition $aggregateClassDefinition, InterfaceToCallRegistry $interfaceToCallRegistry): array
    {
        $aggregateIdentifierGetMethodAttribute = TypeDescriptor::create(AggregateIdentifierMethod::class);
        $aggregateIdentifiers = [];
        $aggregateIdentifierGetMethods = [];

        foreach ($aggregateClassDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = $interfaceToCallRegistry->getFor($aggregateClassDefinition->getClassType()->toString(), $method);
            if ($methodToCheck->hasMethodAnnotation($aggregateIdentifierGetMethodAttribute)) {
                if (! $methodToCheck->hasNoParameters()) {
                    throw NoCorrectIdentifierDefinedException::create($methodToCheck . ' should not have any parameters.');
                }

                /** @var AggregateIdentifierMethod $attribute */
                $attribute = $methodToCheck->getSingleMethodAnnotationOf($aggregateIdentifierGetMethodAttribute);
                $aggregateIdentifiers[$attribute->getIdentifierPropertyName()] = null;
                $aggregateIdentifierGetMethods[$attribute->getIdentifierPropertyName()] = $method;
            }
        }

        $aggregateIdentifierAnnotation = TypeDescriptor::create(AggregateIdentifier::class);
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($aggregateIdentifierAnnotation)) {
                $aggregateIdentifiers[$property->getName()] = null;
            }
        }

        return [$aggregateIdentifiers, $aggregateIdentifierGetMethods];
    }

    private function resolveAggregateVersionProperty(ClassDefinition $aggregateClassDefinition): array
    {
        $aggregateVersionPropertyName = null;
        $isAggregateVersionAutomaticallyIncreased = false;
        $versionAnnotation = TypeDescriptor::create(AggregateVersion::class);
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($versionAnnotation)) {
                $aggregateVersionPropertyName = $property->getName();
                /** @var AggregateVersion $annotation */
                $annotation = $property->getAnnotation($versionAnnotation);
                $isAggregateVersionAutomaticallyIncreased = $annotation->isAutoIncreased();
            }
        }

        return [$aggregateVersionPropertyName, $isAggregateVersionAutomaticallyIncreased];
    }
}
