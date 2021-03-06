<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
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

    private function __construct(ClassDefinition $aggregateClassName, array $metadataIdentifierMapping, ?ClassDefinition $messageClassNameToConvertTo)
    {
        $this->messageClassNameToConvertTo = $messageClassNameToConvertTo;
        $this->aggregateClassName = $aggregateClassName;
        $this->metadataIdentifierMapping = $metadataIdentifierMapping;

        $this->initialize($aggregateClassName, $messageClassNameToConvertTo, $metadataIdentifierMapping);
    }

    public static function createWith(ClassDefinition $aggregateClassName, array $metadataIdentifierMapping, ?ClassDefinition $messageClassNameToConvertTo) : self
    {
        return new self($aggregateClassName, $metadataIdentifierMapping, $messageClassNameToConvertTo);
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        /** @var ConversionService $conversionService */
        $conversionService = $referenceSearchService->get(ConversionService::REFERENCE_NAME);

        Assert::isSubclassOf($conversionService, ConversionService::class, "Have you forgot to register " . ConversionService::REFERENCE_NAME . "?");

        return ServiceActivatorBuilder::createWithDirectReference(
            new AggregateIdentifierRetrevingService($conversionService, new PropertyReaderAccessor(), $this->typeToConvertTo, $this->metadataIdentifierMapping, $this->payloadIdentifierMapping), "convert")
                    ->withOutputMessageChannel($this->getOutputMessageChannelName())
                    ->build($channelResolver, $referenceSearchService);
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [$interfaceToCallRegistry->getFor(AggregateIdentifierRetrevingService::class, "convert")];
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(AggregateIdentifierRetrevingService::class, "convert");
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [ConversionService::REFERENCE_NAME];
    }

    private function hasAccordingIdentifier(ClassDefinition $aggregateClassName, $propertyName): bool
    {
        foreach ($aggregateClassName->getProperties() as $property) {
            if ($property->hasAnnotation(TypeDescriptor::create(AggregateIdentifier::class)) && ($propertyName === $property->getName())) {
                return true;
            }
        }

        return false;
    }

    private function initialize(ClassDefinition $aggregateClassDefinition, ?ClassDefinition $handledMessageClassNameDefinition, array $metadataIdentifierMapping): void
    {
        foreach ($metadataIdentifierMapping as $propertyName => $mappingName) {
            if (!$this->hasAccordingIdentifier($aggregateClassDefinition, $propertyName)) {
                throw InvalidArgumentException::create("Aggregate {$aggregateClassDefinition} has no identifier {$propertyName} and such meta data identifier mapping given.");
            }
        }

        $aggregatePayloadIdentifiersMapping = [];

        $aggregateIdentififerAnnotation = TypeDescriptor::create(AggregateIdentifier::class);
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($aggregateIdentififerAnnotation)) {
                $aggregatePayloadIdentifiersMapping[$property->getName()] = null;
            }
        }

        if (empty($aggregatePayloadIdentifiersMapping)) {
            throw InvalidArgumentException::create("Aggregate {$aggregateClassDefinition} has no identifiers defined. How you forgot to mark #[AggregateIdentifier]?");
        }

        $messageProperties = [];
        if ($handledMessageClassNameDefinition) {
            $targetAggregateIdentifierAnnotation = TypeDescriptor::create(TargetAggregateIdentifier::class);
            foreach ($handledMessageClassNameDefinition->getProperties() as $property) {
                if ($property->hasAnnotation($targetAggregateIdentifierAnnotation)) {
                    /** @var TargetAggregateIdentifier $annotation */
                    $annotation  = $property->getAnnotation($targetAggregateIdentifierAnnotation);
                    $mappingName = $annotation->identifierName ? $annotation->identifierName : $property->getName();

                    if ($aggregateClassDefinition->hasProperty($mappingName) && $aggregateClassDefinition->getProperty($mappingName)->hasAnnotation($aggregateIdentififerAnnotation)) {
                        $aggregatePayloadIdentifiersMapping[$mappingName] = $property->getName();
                    }
                }
            }

            $messageProperties = $handledMessageClassNameDefinition->getProperties();
        }

        foreach ($this->metadataIdentifierMapping as $identifierName => $mapping) {
            if (!in_array($identifierName, array_keys($aggregatePayloadIdentifiersMapping))) {
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
                } else if (is_null($mappingKey) && !$this->hasIdentifierMappingInMetadata($metadataIdentifierMapping, $aggregateIdentifierName)) {
                    throw new InvalidArgumentException("Can't find aggregate identifier mapping `{$aggregateIdentifierName}` in {$handledMessageClassNameDefinition} for {$aggregateClassDefinition}. How you forgot to mark #[TargetAggregateIdentifier]?");
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