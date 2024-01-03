<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\AggregateIdentifierMethod;
use Ecotone\Modelling\Attribute\TargetAggregateIdentifier;

/**
 * Class AggregateMessageConversionServiceBuilder
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateIdentifierRetrevingServiceBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilder
{
    private ?ClassDefinition $messageClassNameToConvertTo;
    private ClassDefinition $aggregateClassName;
    private array $metadataIdentifierMapping;
    private TypeDescriptor $typeToConvertTo;
    private array $payloadIdentifierMapping;

    private function __construct(ClassDefinition $aggregateClassName, array $metadataIdentifierMapping, ?ClassDefinition $messageClassNameToConvertTo, InterfaceToCallRegistry $interfaceToCallRegistry)
    {
        $this->messageClassNameToConvertTo = $messageClassNameToConvertTo;
        $this->aggregateClassName = $aggregateClassName;
        $this->metadataIdentifierMapping = $metadataIdentifierMapping;

        $this->initialize($interfaceToCallRegistry, $aggregateClassName, $messageClassNameToConvertTo, $metadataIdentifierMapping);
    }

    public static function createWith(ClassDefinition $aggregateClassName, array $metadataIdentifierMapping, ?ClassDefinition $messageClassNameToConvertTo, InterfaceToCallRegistry $interfaceToCallRegistry): self
    {
        return new self($aggregateClassName, $metadataIdentifierMapping, $messageClassNameToConvertTo, $interfaceToCallRegistry);
    }

    /**
     * @inheritDoc
     */
    public function compile(MessagingContainerBuilder $builder): Definition
    {
        $aggregateIdentifierRetrevingService = new Definition(
            AggregateIdentifierRetrevingService::class,
            [
                $this->aggregateClassName->getClassType()->toString(),
                new Reference(ConversionService::REFERENCE_NAME),
                new Reference(PropertyReaderAccessor::class),
                $this->typeToConvertTo,
                $this->metadataIdentifierMapping,
                $this->payloadIdentifierMapping,
            ]
        );
        $serviceActivatorBuilder = ServiceActivatorBuilder::createWithDefinition($aggregateIdentifierRetrevingService, 'convert')
            ->withOutputMessageChannel($this->getOutputMessageChannelName());
        return $serviceActivatorBuilder->compile($builder);
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
            if ($property->hasAnnotation(TypeDescriptor::create(AggregateIdentifier::class)) && ($propertyName === $property->getName())) {
                return true;
            }
        }
        $aggregateIdentifierMethod = TypeDescriptor::create(AggregateIdentifierMethod::class);

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

    private function initialize(InterfaceToCallRegistry $interfaceToCallRegistry, ClassDefinition $aggregateClassDefinition, ?ClassDefinition $handledMessageClassNameDefinition, array $metadataIdentifierMapping): void
    {
        $aggregatePayloadIdentifiersMapping = [];

        $aggregateIdentifierAnnotation = TypeDescriptor::create(AggregateIdentifier::class);
        $aggregateIdentifierMethod = TypeDescriptor::create(AggregateIdentifierMethod::class);
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($aggregateIdentifierAnnotation)) {
                $aggregatePayloadIdentifiersMapping[$property->getName()] = null;
            }
        }
        foreach ($aggregateClassDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = $interfaceToCallRegistry->getFor($aggregateClassDefinition->getClassType()->toString(), $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateIdentifierMethod)) {
                /** @var AggregateIdentifierMethod $attribute */
                $attribute = $methodToCheck->getSingleMethodAnnotationOf($aggregateIdentifierMethod);
                $aggregatePayloadIdentifiersMapping[$attribute->getIdentifierPropertyName()] = null;
            }
        }

        if (empty($aggregatePayloadIdentifiersMapping)) {
            throw InvalidArgumentException::create("Aggregate {$aggregateClassDefinition} has no identifiers defined. How you forgot to mark #[AggregateIdentifier]?");
        }

        foreach ($metadataIdentifierMapping as $propertyName => $mappingName) {
            if (! $this->hasAccordingIdentifier($interfaceToCallRegistry, $aggregateClassDefinition, $propertyName)) {
                throw InvalidArgumentException::create("Aggregate {$aggregateClassDefinition} has no identifier {$propertyName} for metadata identifier mapping.");
            }
        }

        $messageProperties = [];
        if ($handledMessageClassNameDefinition) {
            $targetAggregateIdentifierAnnotation = TypeDescriptor::create(TargetAggregateIdentifier::class);
            foreach ($handledMessageClassNameDefinition->getProperties() as $property) {
                if ($property->hasAnnotation($targetAggregateIdentifierAnnotation)) {
                    /** @var TargetAggregateIdentifier $annotation */
                    $annotation  = $property->getAnnotation($targetAggregateIdentifierAnnotation);
                    $mappingName = $annotation->identifierName ? $annotation->identifierName : $property->getName();

                    if ($aggregateClassDefinition->hasProperty($mappingName) && $aggregateClassDefinition->getProperty($mappingName)->hasAnnotation($aggregateIdentifierAnnotation)) {
                        $aggregatePayloadIdentifiersMapping[$mappingName] = $property->getName();
                    }
                }
            }

            $messageProperties = $handledMessageClassNameDefinition->getProperties();
        }

        foreach ($this->metadataIdentifierMapping as $identifierName => $mapping) {
            if (! in_array($identifierName, array_keys($aggregatePayloadIdentifiersMapping))) {
                throw ConfigurationException::create("Aggregate {$aggregateClassDefinition} for {$handledMessageClassNameDefinition} has metadata mapping for non existing identifier key {$identifierName}. It should be {\"aggregateId\":\"metadataIdKey\"}?");
            }
        }

        foreach ($aggregatePayloadIdentifiersMapping as $aggregateIdentifierName => $aggregateIdentifierMappingKey) {
            if (is_null($aggregateIdentifierMappingKey)) {
                $mappingKey = null;
                foreach ($messageProperties as $property) {
                    if ($aggregateIdentifierName === $property->getName()) {
                        $mappingKey = $property->getName();
                    }
                }

                if (is_null($handledMessageClassNameDefinition) && is_null($mappingKey)) {
                    $aggregatePayloadIdentifiersMapping[$aggregateIdentifierName] = $aggregateIdentifierName;
                } elseif (is_null($mappingKey) && ! $this->hasIdentifierMappingInMetadata($metadataIdentifierMapping, $aggregateIdentifierName)) {
                    /** NO mapping available, identifier should come from message headers under "aggregate.id" */
                    $aggregatePayloadIdentifiersMapping = [];
                } else {
                    $aggregatePayloadIdentifiersMapping[$aggregateIdentifierName] = $mappingKey;
                }
            }
        }

        $this->payloadIdentifierMapping = $aggregatePayloadIdentifiersMapping;
        $this->typeToConvertTo          = $handledMessageClassNameDefinition ? $handledMessageClassNameDefinition->getClassType() : TypeDescriptor::createArrayType();
    }

    private function hasIdentifierMappingInMetadata(array $metadataIdentifierMapping, $aggregateIdentifierName): bool
    {
        foreach ($metadataIdentifierMapping as $identifierNameHeaderMapping => $headerName) {
            if ($aggregateIdentifierName == $identifierNameHeaderMapping) {
                return true;
            }
        }

        return false;
    }
}
