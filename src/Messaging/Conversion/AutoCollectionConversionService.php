<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\Handler\Type;

use function is_iterable;

/**
 * licence Apache-2.0
 */
class AutoCollectionConversionService implements ConversionService
{
    /**
     * @param Converter[] $converters
     * @param array<string, int|false> $convertersCache value is index of converter in $this->converters or false if not found
     */
    public function __construct(private array $converters, private array $convertersCache = [])
    {
    }

    /**
     * @param Converter[] $converters
     * @return AutoCollectionConversionService
     */
    public static function createWith(array $converters): self
    {
        return new self($converters);
    }

    /**
     * @return AutoCollectionConversionService
     */
    public static function createEmpty(): self
    {
        return new self([]);
    }

    public function convert($source, Type $sourcePHPType, MediaType $sourceMediaType, Type $targetPHPType, MediaType $targetMediaType)
    {
        if (is_null($source)) {
            return $source;
        }

        if ($converter = $this->getConverter($sourcePHPType, $sourceMediaType, $targetPHPType, $targetMediaType)) {
            return $converter->convert($source, $sourcePHPType, $sourceMediaType, $targetPHPType, $targetMediaType, $this);
        }

        if (is_iterable($source) && $targetPHPType->isIterable() && $targetPHPType instanceof Type\GenericType) {
            $converted = [];
            $targetValueType = $this->getValueTypeFromCollectionType($targetPHPType);
            foreach ($source as $k => $v) {
                $converted[$k] = $this->convert($v, Type::createFromVariable($v), MediaType::createApplicationXPHP(), $targetValueType, $targetMediaType);
            }
            return $converted;
        }

        throw ConversionException::create("Converter was not found for {$sourceMediaType}:{$sourcePHPType} to {$targetMediaType}:{$targetPHPType};");
    }

    public function canConvert(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        if ($this->getConverter($sourceType, $sourceMediaType, $targetType, $targetMediaType)) {
            ;
            return true;
        }
        if ($sourceType->isIterable() && $sourceType instanceof Type\GenericType
            && $targetType->isIterable() && $targetType instanceof Type\GenericType) {
            return (bool) $this->getConverter(
                $this->getValueTypeFromCollectionType($sourceType),
                $sourceMediaType,
                $this->getValueTypeFromCollectionType($targetType),
                $targetMediaType
            );
        }
        return false;
    }

    private function getValueTypeFromCollectionType(Type\GenericType $collectionType): Type
    {
        return match (count($collectionType->genericTypes)) {
            1 => $collectionType->genericTypes[0],
            default => $collectionType->genericTypes[1],
        };
    }

    /**
     * @param Type $sourceType
     * @param MediaType $sourceMediaType
     * @param Type $targetType
     * @param MediaType $targetMediaType
     * @return Converter|null
     */
    private function getConverter(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): ?Converter
    {
        $cacheKey = $sourceType->toString() . '|' . $sourceMediaType->toString() . '->' . $targetType->toString() . '|' . $targetMediaType->toString();
        if (isset($this->convertersCache[$cacheKey])) {
            if ($this->convertersCache[$cacheKey] === false) {
                return null;
            }
            return $this->converters[$this->convertersCache[$cacheKey]];
        }

        foreach ($this->converters as $index => $converter) {
            if ($converter->matches($sourceType, $sourceMediaType, $targetType, $targetMediaType)) {
                $this->convertersCache[$cacheKey] = $index;
                return $converter;
            }
        }

        $this->convertersCache[$cacheKey] = false;

        return null;
    }
}
