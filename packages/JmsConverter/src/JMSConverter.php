<?php

namespace Ecotone\JMSConverter;

use Ecotone\Messaging\Conversion\ConversionException;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;
use InvalidArgumentException;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerInterface;
use RuntimeException;

class JMSConverter implements Converter
{
    public const SERIALIZE_NULL_PARAMETER = 'serializeNull';

    private SerializerInterface $serializer;
    private JMSConverterConfiguration $jmsConverterConfiguration;

    public function __construct(Serializer $serializer, JMSConverterConfiguration $jmsConverterConfiguration)
    {
        $this->serializer = $serializer;
        $this->jmsConverterConfiguration = $jmsConverterConfiguration;
    }

    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        $serializeNulls = $targetMediaType->hasParameter(self::SERIALIZE_NULL_PARAMETER) ? $targetMediaType->getParameter(self::SERIALIZE_NULL_PARAMETER) === 'true' : $this->jmsConverterConfiguration->getDefaultNullSerialization();

        $context = new SerializationContext();
        if ($serializeNulls) {
            $context->setSerializeNull(true);
        }

        try {
            if ($sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP) && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)) {
                if ($sourceType->isIterable() && ! $targetType->isNonCollectionArray()) {
                    return $this->serializer->fromArray($source, $targetType->toString());
                } elseif ($targetType->isIterable()) {
                    return $this->serializer->toArray($source, $context);
                } else {
                    throw new InvalidArgumentException("Can't conversion from {$sourceMediaType->toString()}:{$sourceType->toString()} to {$targetMediaType->toString()}:{$targetMediaType->toString()}");
                }
            }

            if ($targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)) {
                if ($sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_JSON)) {
                    return $this->serializer->deserialize($source, $targetType->toString(), 'json');
                } elseif ($sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_XML)) {
                    return $this->serializer->deserialize($source, $targetType->toString(), 'xml');
                } else {
                    throw new InvalidArgumentException("Can't conversion from {$sourceMediaType->toString()}:{$sourceType->toString()} to {$targetMediaType->toString()}:{$targetMediaType->toString()}");
                }
            } else {
                if ($targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_JSON)) {
                    return $this->serializer->serialize($source, 'json', $context);
                } elseif ($targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_XML)) {
                    return $this->serializer->serialize($source, 'xml', $context);
                } else {
                    throw new InvalidArgumentException("Can't conversion from {$sourceMediaType->toString()}:{$sourceType->toString()} to {$targetMediaType->toString()}:{$targetMediaType->toString()}");
                }
            }
        } catch (RuntimeException $exception) {
            throw ConversionException::createFromPreviousException("Can't convert from {$sourceMediaType}:{$sourceType} to {$targetMediaType}:{$targetType} " . $exception->getMessage(), $exception);
        }
    }

    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        if ($sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP) && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)) {
            return $sourceType->isIterable() && ($targetType->isClassOrInterface() || $targetType->isIterable())
                   || ($sourceType->isClassOrInterface() || $sourceType->isIterable()) && $targetType->isIterable();
        }

        if (! $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_JSON) && ! $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_XML)
            && ! $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_JSON) && ! $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_XML)
        ) {
            return false;
        }

        return true;
    }
}
