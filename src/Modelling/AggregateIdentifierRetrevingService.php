<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\TargetAggregateIdentifier;

/**
 * Class AggregateMessageConversionService
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateIdentifierRetrevingService
{
    private ConversionService $conversionService;
    private TypeDescriptor $typeToConvertTo;
    private array $payloadIdentifierMapping;
    private array $metadataIdentifierMapping;
    private PropertyReaderAccessor $propertyReaderAccessor;

    public function __construct(ConversionService $conversionService, PropertyReaderAccessor $propertyReaderAccessor, ClassDefinition $aggregateClassDefinition, array $metadataIdentifierMapping, ?ClassDefinition $messageClassNameToConvertTo)
    {
        $this->conversionService         = $conversionService;
        $this->propertyReaderAccessor    = $propertyReaderAccessor;
        $this->metadataIdentifierMapping = $metadataIdentifierMapping;
        $this->initialize($aggregateClassDefinition, $messageClassNameToConvertTo, $metadataIdentifierMapping);
    }

    public function convert(Message $message): Message
    {
//        @TODO handle situation when payload is class and typeToConvert is array

        $payload   = $message->getPayload();
        $mediaType = $message->getHeaders()->containsKey(MessageHeaders::CONTENT_TYPE)
            ? MediaType::parseMediaType($message->getHeaders()->get(MessageHeaders::CONTENT_TYPE))
            : MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::createFromVariable($payload)->toString());
        if ($this->conversionService->canConvert(
            TypeDescriptor::createFromVariable($payload),
            $mediaType,
            $this->typeToConvertTo,
            MediaType::createApplicationXPHPWithTypeParameter($this->typeToConvertTo->toString())
        )) {
            $payload = $this->conversionService
                ->convert(
                    $payload,
                    TypeDescriptor::createFromVariable($payload),
                    $mediaType,
                    $this->typeToConvertTo,
                    MediaType::createApplicationXPHPWithTypeParameter($this->typeToConvertTo->toString())
                );
        }

        $aggregateIdentifiers = [];
        foreach ($this->payloadIdentifierMapping as $aggregateIdentifierName => $aggregateIdentifierMappingName) {
            if (is_null($aggregateIdentifierMappingName)) {
                $aggregateIdentifiers[$aggregateIdentifierName] = null;
                continue;
            }

            $aggregateIdentifiers[$aggregateIdentifierName] =
                $this->propertyReaderAccessor->hasPropertyValue(PropertyPath::createWith($aggregateIdentifierMappingName), $payload)
                    ? $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($aggregateIdentifierMappingName), $payload)
                    : null;
        }
        $metadata = $message->getHeaders()->headers();
        foreach ($this->metadataIdentifierMapping as $identifierName => $headerName) {
            if (array_key_exists($headerName, $metadata)) {
                $aggregateIdentifiers[$identifierName] = $metadata[$headerName];
            }
        }

        return MessageBuilder::fromMessage($message)
            ->setHeader(AggregateMessage::AGGREGATE_ID, $aggregateIdentifiers)
            ->build();
    }

    private function initialize(ClassDefinition $aggregateClassDefinition, ?ClassDefinition $handledMessageClassNameDefinition, array $metadataIdentifierMapping): void
    {
        $aggregatePayloadIdentifiersMapping = [];

        $aggregateIdentififerAnnotation = TypeDescriptor::create(AggregateIdentifier::class);
        foreach ($aggregateClassDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($aggregateIdentififerAnnotation)) {
                $aggregatePayloadIdentifiersMapping[$property->getName()] = null;
            }
        }

        if (empty($aggregatePayloadIdentifiersMapping)) {
            throw InvalidArgumentException::create("Aggregate {$aggregateClassDefinition} has no identifiers defined. How you forgot to mark @AggregateIdentifier?");
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
                    throw new InvalidArgumentException("Can't find aggregate identifier mapping `{$aggregateIdentifierName}` in {$handledMessageClassNameDefinition} for {$aggregateClassDefinition}. How you forgot to mark @TargetAggregateIdentifier?");
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