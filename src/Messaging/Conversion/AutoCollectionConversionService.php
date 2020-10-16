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

    /**
     * @param mixed $source
     * @param Type $sourceType
     * @param MediaType $sourceMediaType
     * @param Type $targetType
     * @param MediaType $targetMediaType
     * @return mixed
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        Assert::isFalse($sourceType->isUnionType(), "Can't convert from Union source type {$sourceMediaType}:{$sourceType} to {$targetMediaType}:{$targetType}");
        if (is_null($source)) {
            return $source;
        }

        $targetType = $this->getTargetType($sourceType, $sourceMediaType, $targetType, $targetMediaType);
        Assert::isObject($targetType, "Converter was not found for {$sourceMediaType}:{$sourceType} to {$targetMediaType}:{$targetType};");
        $converter = $this->getConverter($sourceType, $sourceMediaType, $targetType, $targetMediaType);
        Assert::isObject($converter, "Converter was not found for {$sourceMediaType}:{$sourceType} to {$targetMediaType}:{$targetType};");

        return $converter->convert($source, $sourceType, $sourceMediaType, $targetType, $targetMediaType);
    }

    /**
     * @param Type $sourceType
     * @param Type $targetType
     * @param MediaType $sourceMediaType
     * @param MediaType $targetMediaType
     * @return bool
     */
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

    private function getTargetType(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType) : ?TypeDescriptor
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

        return null;
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