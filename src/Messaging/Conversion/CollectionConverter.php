<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\Handler\Type;

/**
 * Class CollectionConverter
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class CollectionConverter implements Converter
{
    private Converter $converterForSingleType;

    /**
     * CollectionConverter constructor.
     * @param Converter $converterForSingleType
     */
    private function __construct(Converter $converterForSingleType)
    {
        $this->converterForSingleType = $converterForSingleType;
    }

    /**
     * @param Converter $converterForSingleType
     * @return CollectionConverter
     */
    public static function createForConverter(Converter $converterForSingleType): self
    {
        return new self($converterForSingleType);
    }

    /**
     * @inheritDoc
     */
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): array
    {
        if (! $sourceType instanceof Type\GenericType || ! $targetType instanceof Type\GenericType) {
            throw new ConversionException("Source and target type must be generic types, {$sourceType} and {$targetType} given.");
        }
        $collection = [];
        foreach ($source as $element) {
            $collection[] = $this->converterForSingleType->convert(
                $element,
                $sourceType->genericTypes[0],
                MediaType::createApplicationXPHP(),
                $targetType->genericTypes[0],
                MediaType::createApplicationXPHP(),
            );
        }

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType instanceof Type\GenericType && $targetType instanceof Type\GenericType
            && $sourceType->isCollection() && $targetType->isCollection()
            && $this->converterForSingleType->matches(
                $sourceType->genericTypes[0],
                MediaType::createApplicationXPHP(),
                $targetType->genericTypes[0],
                MediaType::createApplicationXPHP(),
            );
    }
}
