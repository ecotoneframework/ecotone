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
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\TargetAggregateIdentifier;

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

    public function __construct(private string $aggregateClassName, ConversionService $conversionService, PropertyReaderAccessor $propertyReaderAccessor, TypeDescriptor $typeToConvertTo, array $metadataIdentifierMapping, array $payloadIdentifierMapping)
    {
        $this->conversionService         = $conversionService;
        $this->propertyReaderAccessor    = $propertyReaderAccessor;
        $this->metadataIdentifierMapping = $metadataIdentifierMapping;
        $this->payloadIdentifierMapping = $payloadIdentifierMapping;
        $this->typeToConvertTo = $typeToConvertTo;
    }

    public function convert(Message $message): Message
    {
        if ($message->getHeaders()->containsKey(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER)) {
            $aggregateIds = $message->getHeaders()->get(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER);
            $aggregateIds = is_array($aggregateIds) ? $aggregateIds : [array_key_first($this->payloadIdentifierMapping) => $aggregateIds];

            return MessageBuilder::fromMessage($message)
                ->setHeader(AggregateMessage::AGGREGATE_ID, AggregateId::resolveArrayOfIdentifiers($this->aggregateClassName, $aggregateIds))
                ->build();
        }

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
            ->setHeader(AggregateMessage::AGGREGATE_ID, AggregateId::resolveArrayOfIdentifiers($this->aggregateClassName, $aggregateIdentifiers))
            ->build();
    }
}