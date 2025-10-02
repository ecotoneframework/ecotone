<?php

namespace Ecotone\Modelling;

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
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\AggregateIdentifierMethod;
use Ecotone\Modelling\Attribute\TargetAggregateIdentifier;

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
    private Type $typeToConvertTo;
    private array $messageIdentifierMapping;

    private function __construct(
        private ClassDefinition $aggregateClassName,
        private array $metadataIdentifierMapping,
        private array $identifierMapping,
        ?ClassDefinition $messageClassNameToConvertTo,
        InterfaceToCallRegistry $interfaceToCallRegistry
    ) {
        $this->initialize($interfaceToCallRegistry, $aggregateClassName, $messageClassNameToConvertTo, $metadataIdentifierMapping, $identifierMapping);
    }

    public static function createWith(ClassDefinition $aggregateClassName, array $metadataIdentifierMapping, array $identifierMapping, ?ClassDefinition $messageClassNameToConvertTo, InterfaceToCallRegistry $interfaceToCallRegistry): self
    {
        return new self($aggregateClassName, $metadataIdentifierMapping, $identifierMapping, $messageClassNameToConvertTo, $interfaceToCallRegistry);
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
                $this->typeToConvertTo,
                $this->metadataIdentifierMapping,
                $this->messageIdentifierMapping,
                $this->identifierMapping,
                Reference::to(ExpressionEvaluationService::REFERENCE),
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
            if ($property->hasAnnotation(Type::attribute(AggregateIdentifier::class)) && ($propertyName === $property->getName())) {
                return true;
            }
        }
        $aggregateIdentifierMethod = Type::attribute(AggregateIdentifierMethod::class);

        foreach ($aggregateClassName->getPublicMethodNames() as $method) {
            $methodToCheck = $interfaceToCallRegistry->getFor($aggregateClassName->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateIdentifierMethod)) {
                /** @var AggregateIdentifierMethod $attribute */
                $attribute = $methodToCheck->getSingleMethodAnnotationOf($aggregateIdentifierMethod);

                if ($attribute->getIdentifierPropertyName() === $propertyName) {
                    return true;
                }
            }
        }

        return false;
    }

    private function initialize(
        InterfaceToCallRegistry $interfaceToCallRegistry,
        ClassDefinition $aggregateClassDefinition,
        ?ClassDefinition $handledMessageClassNameDefinition,
        array $metadataIdentifierMapping,
        array $identifierMapping
    ): void {
        $messageIdentifiersMapping = [];

        $aggregateIdentifierAnnotation = Type::attribute(AggregateIdentifier::class);
        $aggregateIdentifierMethod = Type::attribute(AggregateIdentifierMethod::class);
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($aggregateIdentifierAnnotation)) {
                $messageIdentifiersMapping[$property->getName()] = null;
            }
        }
        foreach ($aggregateClassDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = $interfaceToCallRegistry->getFor($aggregateClassDefinition->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateIdentifierMethod)) {
                /** @var AggregateIdentifierMethod $attribute */
                $attribute = $methodToCheck->getSingleMethodAnnotationOf($aggregateIdentifierMethod);
                $messageIdentifiersMapping[$attribute->getIdentifierPropertyName()] = null;
            }
        }

        if (empty($messageIdentifiersMapping)) {
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

        $messageProperties = [];
        if ($handledMessageClassNameDefinition) {
            $targetAggregateIdentifierAnnotation = Type::attribute(TargetAggregateIdentifier::class);
            foreach ($handledMessageClassNameDefinition->getProperties() as $property) {
                if ($property->hasAnnotation($targetAggregateIdentifierAnnotation)) {
                    /** @var TargetAggregateIdentifier $annotation */
                    $annotation  = $property->getAnnotation($targetAggregateIdentifierAnnotation);
                    $mappingName = $annotation->identifierName ? $annotation->identifierName : $property->getName();

                    if ($aggregateClassDefinition->hasProperty($mappingName) && $aggregateClassDefinition->getProperty($mappingName)->hasAnnotation($aggregateIdentifierAnnotation)) {
                        $messageIdentifiersMapping[$mappingName] = $property->getName();
                    }
                }
            }

            $messageProperties = $handledMessageClassNameDefinition->getProperties();
        }

        foreach ($messageIdentifiersMapping as $aggregateIdentifierName => $aggregateIdentifierMappingKey) {
            if (is_null($aggregateIdentifierMappingKey)) {
                $mappingKey = null;
                foreach ($messageProperties as $property) {
                    if ($aggregateIdentifierName === $property->getName()) {
                        $mappingKey = $property->getName();
                    }
                }

                if (is_null($handledMessageClassNameDefinition) && is_null($mappingKey)) {
                    $messageIdentifiersMapping[$aggregateIdentifierName] = $aggregateIdentifierName;
                } elseif (is_null($mappingKey) && ! $this->hasRuntimeIdentifierMapping($metadataIdentifierMapping, $aggregateIdentifierName) && ! $this->hasRuntimeIdentifierMapping($identifierMapping, $aggregateIdentifierName)) {
                    /** NO mapping available, identifier should come from message headers under "aggregate.id" */
                    $messageIdentifiersMapping = [];
                } else {
                    $messageIdentifiersMapping[$aggregateIdentifierName] = $mappingKey;
                }
            }
        }

        $this->messageIdentifierMapping = $messageIdentifiersMapping;
        $this->typeToConvertTo          = $handledMessageClassNameDefinition ? $handledMessageClassNameDefinition->getClassType() : Type::array();
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
