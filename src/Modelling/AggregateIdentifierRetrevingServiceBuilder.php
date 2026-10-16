<?php

namespace Ecotone\Modelling;

use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\IdentifierMethod;
use Ecotone\Api\Attribute\TargetIdentifier;
use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;

use function is_null;

use ReflectionClass;

/**
 * Class AggregateMessageConversionServiceBuilder
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class AggregateIdentifierRetrevingServiceBuilder implements CompilableBuilder
{
    /** @var array<string, array<string, string|null>> Per-class identifier mappings */
    private array $perClassIdentifierMappings = [];

    /**
     * @param ClassDefinition[] $handledMessageClassDefinitions
     */
    private function __construct(
        private ClassDefinition $aggregateClassName,
        private array $metadataIdentifierMapping,
        private array $identifierMapping,
        array $handledMessageClassDefinitions,
        InterfaceToCallRegistry $interfaceToCallRegistry
    ) {
        $this->initialize($interfaceToCallRegistry, $aggregateClassName, $handledMessageClassDefinitions, $metadataIdentifierMapping, $identifierMapping);
    }

    /**
     * @param ClassDefinition[] $handledMessageClassDefinitions
     */
    public static function createWith(ClassDefinition $aggregateClassName, array $metadataIdentifierMapping, array $identifierMapping, array $handledMessageClassDefinitions, InterfaceToCallRegistry $interfaceToCallRegistry): self
    {
        return new self($aggregateClassName, $metadataIdentifierMapping, $identifierMapping, $handledMessageClassDefinitions, $interfaceToCallRegistry);
    }

    /**
     * @inheritDoc
     */
    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(
            AggregateIdentifierRetrevingService::class,
            [
                $this->aggregateClassName->getClassType()->toString(),
                Reference::to(ConversionService::REFERENCE_NAME),
                Reference::to(PropertyReaderAccessor::class),
                $this->metadataIdentifierMapping,
                $this->identifierMapping,
                Reference::to(ExpressionEvaluationService::REFERENCE),
                $this->perClassIdentifierMappings,
                Reference::to(MessageBuilder::class),
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(AggregateIdentifierRetrevingService::class, 'convert');
    }

    private function hasAccordingIdentifier(InterfaceToCallRegistry $interfaceToCallRegistry, ClassDefinition $aggregateClassName, $propertyName): bool
    {
        foreach ($aggregateClassName->getProperties() as $property) {
            if ($property->hasAnnotation(Type::attribute(Identifier::class)) && ($propertyName === $property->getName())) {
                return true;
            }
        }
        $aggregateIdentifierMethod = Type::attribute(IdentifierMethod::class);

        foreach ($aggregateClassName->getPublicMethodNames() as $method) {
            $methodToCheck = $interfaceToCallRegistry->getFor($aggregateClassName->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateIdentifierMethod)) {
                /** @var IdentifierMethod $attribute */
                $attribute = $methodToCheck->getSingleMethodAnnotationOf($aggregateIdentifierMethod);

                if ($attribute->identifierPropertyName === $propertyName) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param ClassDefinition[] $handledMessageClassDefinitions
     */
    private function initialize(
        InterfaceToCallRegistry $interfaceToCallRegistry,
        ClassDefinition $aggregateClassDefinition,
        array $handledMessageClassDefinitions,
        array $metadataIdentifierMapping,
        array $identifierMapping
    ): void {
        $aggregateIdentifierAnnotation = Type::attribute(Identifier::class);
        $aggregateIdentifierMethod = Type::attribute(IdentifierMethod::class);

        $baseMessageIdentifiersMapping = [];
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($aggregateIdentifierAnnotation)) {
                $baseMessageIdentifiersMapping[$property->getName()] = null;
            }
        }
        foreach ($aggregateClassDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = $interfaceToCallRegistry->getFor($aggregateClassDefinition->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateIdentifierMethod)) {
                /** @var IdentifierMethod $attribute */
                $attribute = $methodToCheck->getSingleMethodAnnotationOf($aggregateIdentifierMethod);
                $baseMessageIdentifiersMapping[$attribute->identifierPropertyName] = null;
            }
        }

        if (empty($baseMessageIdentifiersMapping)) {
            throw InvalidArgumentException::create("Aggregate {$aggregateClassDefinition} has no identifiers defined. Have you forgot to add #[Identifier]?");
        }

        foreach ($metadataIdentifierMapping as $propertyName => $mappingName) {
            if (! $this->hasAccordingIdentifier($interfaceToCallRegistry, $aggregateClassDefinition, $propertyName)) {
                throw InvalidArgumentException::create("Aggregate {$aggregateClassDefinition} has no identifier {$propertyName} for metadata identifier mapping.");
            }
        }
        foreach ($identifierMapping as $propertyName => $mappingName) {
            if (! $this->hasAccordingIdentifier($interfaceToCallRegistry, $aggregateClassDefinition, $propertyName)) {
                throw InvalidArgumentException::create("Aggregate {$aggregateClassDefinition} has no identifier {$propertyName} for identifier mapping.");
            }
        }

        foreach ($handledMessageClassDefinitions as $handledMessageClassDefinition) {
            $className = $handledMessageClassDefinition->getClassType()->toString();
            $this->perClassIdentifierMappings[$className] = $this->buildIdentifierMappingForClass(
                $aggregateClassDefinition,
                $handledMessageClassDefinition,
                $baseMessageIdentifiersMapping,
                $metadataIdentifierMapping,
                $identifierMapping,
                $aggregateIdentifierAnnotation
            );
        }

        if ($handledMessageClassDefinitions === []) {
            $this->perClassIdentifierMappings[''] = $this->buildIdentifierMappingForClass(
                $aggregateClassDefinition,
                null,
                $baseMessageIdentifiersMapping,
                $metadataIdentifierMapping,
                $identifierMapping,
                $aggregateIdentifierAnnotation
            );
        }
    }

    private function buildIdentifierMappingForClass(
        ClassDefinition $aggregateClassDefinition,
        ?ClassDefinition $handledMessageClassDefinition,
        array $baseMessageIdentifiersMapping,
        array $metadataIdentifierMapping,
        array $identifierMapping,
        Type $aggregateIdentifierAnnotation
    ): array {
        $messageIdentifiersMapping = $baseMessageIdentifiersMapping;
        $messageProperties = [];

        if ($handledMessageClassDefinition) {
            $targetIdentifierAnnotation = Type::attribute(TargetIdentifier::class);
            foreach ($handledMessageClassDefinition->getProperties() as $property) {
                if ($property->hasAnnotation($targetIdentifierAnnotation)) {
                    /** @var TargetIdentifier $annotation */
                    $annotation  = $property->getAnnotation($targetIdentifierAnnotation);
                    $mappingName = $annotation->identifierName ? $annotation->identifierName : $property->getName();

                    if ($aggregateClassDefinition->hasProperty($mappingName) && $aggregateClassDefinition->getProperty($mappingName)->hasAnnotation($aggregateIdentifierAnnotation)) {
                        $messageIdentifiersMapping[$mappingName] = $property->getName();
                    }
                }
            }

            $messageProperties = $handledMessageClassDefinition->getProperties();
        }

        foreach ($messageIdentifiersMapping as $aggregateIdentifierName => $aggregateIdentifierMappingKey) {
            if (is_null($aggregateIdentifierMappingKey)) {
                $mappingKey = null;
                foreach ($messageProperties as $property) {
                    if ($aggregateIdentifierName === $property->getName()) {
                        $mappingKey = $property->getName();
                    }
                }

                if ((is_null($handledMessageClassDefinition) || $this->isInterfaceOrAbstractClass($handledMessageClassDefinition)) && is_null($mappingKey)) {
                    $messageIdentifiersMapping[$aggregateIdentifierName] = $aggregateIdentifierName;
                } elseif (is_null($mappingKey) && ! $this->hasRuntimeIdentifierMapping($metadataIdentifierMapping, $aggregateIdentifierName) && ! $this->hasRuntimeIdentifierMapping($identifierMapping, $aggregateIdentifierName)) {
                    /** NO mapping available, identifier should come from message headers under "aggregate.id" */
                    $messageIdentifiersMapping = [];
                } else {
                    $messageIdentifiersMapping[$aggregateIdentifierName] = $mappingKey;
                }
            }
        }

        return $messageIdentifiersMapping;
    }

    private function isInterfaceOrAbstractClass(ClassDefinition $classDefinition): bool
    {
        $className = $classDefinition->getClassType()->toString();

        return interface_exists($className) || (class_exists($className) && (new ReflectionClass($className))->isAbstract());
    }

    private function hasRuntimeIdentifierMapping(array $metadataIdentifierMapping, $aggregateIdentifierName): bool
    {
        foreach ($metadataIdentifierMapping as $identifierNameHeaderMapping => $headerName) {
            if ($aggregateIdentifierName == $identifierNameHeaderMapping) {
                return true;
            }
        }

        return false;
    }
}
