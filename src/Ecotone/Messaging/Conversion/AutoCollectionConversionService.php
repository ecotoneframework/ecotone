<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\Assert;

/**
 * Class ConversionService
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AutoCollectionConversionService implements ConversionService
{
    /**
     * @var Converter[]
     */
    private ?array $converters;

    /**
     * ConversionService constructor.
     * @param Converter[] $converters
     */
    private function __construct(array $converters)
    {
        $this->initialize($converters);
    }

    /**
     * @param Converter[] $converters
     * @return AutoCollectionConversionService
     */
    public static function createWith(array $converters) : self
    {
        return new self($converters);
    }

    /**
     * @return AutoCollectionConversionService
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    public function convert($source, Type $sourcePHPType, MediaType $sourceMediaType, Type $targetPHPType, MediaType $targetMediaType)
    {
        Assert::isFalse($sourcePHPType->isUnionType(), "Can't convert from Union source type {$sourceMediaType}:{$sourcePHPType} to {$targetMediaType}:{$targetPHPType}");
        if (is_null($source)) {
            return $source;
        }

        $targetPHPType = $this->getTargetType($sourcePHPType, $sourceMediaType, $targetPHPType, $targetMediaType);
        $converter = $this->getConverter($sourcePHPType, $sourceMediaType, $targetPHPType, $targetMediaType);
        Assert::isObject($converter, "Converter was not found for {$sourceMediaType}:{$sourcePHPType} to {$targetMediaType}:{$targetPHPType};");

        return $converter->convert($source, $sourcePHPType, $sourceMediaType, $targetPHPType, $targetMediaType);
    }

    public function canConvert(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType) : bool
    {
        return (bool)$this->getConverter($sourceType, $sourceMediaType, $targetType, $targetMediaType);
    }

    /**
     * @param Type $sourceType
     * @param MediaType $sourceMediaType
     * @param Type $targetType
     * @param MediaType $targetMediaType
     * @return Converter|null
     */
    private function getConverter(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType) : ?Converter
    {
        $targetType = $this->getTargetType($sourceType, $sourceMediaType, $targetType, $targetMediaType);
        if (!$targetType) {
            return null;
        }

        foreach ($this->converters as $converter) {
            if ($converter->matches($sourceType, $sourceMediaType, $targetType, $targetMediaType)) {
                return $converter;
            }
        }

        return null;
    }

    private function getTargetType(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType) : TypeDescriptor
    {
        foreach ($this->converters as $converter) {
            /** @var TypeDescriptor[] $targetTypesToCheck */
            $targetTypesToCheck = [];
            if (!$targetType->isUnionType()) {
                $targetTypesToCheck[] = $targetType;
            }else {
                $targetTypesToCheck = $targetType->getUnionTypes();
            }

            foreach ($targetTypesToCheck as $targetTypeToCheck) {
                if ($targetTypeToCheck->equals(TypeDescriptor::create(TypeDescriptor::NULL))) {
                    continue;
                }

                if ($converter->matches($sourceType, $sourceMediaType, $targetTypeToCheck, $targetMediaType)) {
                    return $targetTypeToCheck;
                }
            }
        }

        return $targetType->isUnionType() ? $targetType->getUnionTypes()[0] : $targetType;
    }

    /**
     * @param Converter[] $converters
     */
    private function initialize(array $converters) : void
    {
        $this->converters = $converters;

        foreach ($converters as $converter) {
            $this->converters[] = CollectionConverter::createForConverter($converter);
        }
    }
}