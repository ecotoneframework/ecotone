<?php


namespace Ecotone\Messaging\Conversion;


use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;

class InMemoryConversionService implements ConversionService
{
    private array $convertTo;

    private function __construct(array $convertTo)
    {
        $this->convertTo = $convertTo;
    }

    public static function createWithConversion(string $sourceMediaType, string $sourceType, string $targetMediaType, string $targetType, $conversionResult) : self
    {
        return new self([
            [
                "sourceMediaType" => MediaType::parseMediaType($sourceMediaType),
                "sourceType" => TypeDescriptor::create($sourceType),
                "targetMediaType" => MediaType::parseMediaType($targetMediaType),
                "targetType" => TypeDescriptor::create($targetType),
                "result" => $conversionResult
            ]
        ]);
    }

    public static function createWithoutConversion() : self
    {
        return new self([]);
    }

    public function convert($source, Type $sourcePHPType, MediaType $sourceMediaType, Type $targetPHPType, MediaType $targetMediaType)
    {
        $result = $this->getConversionResult($sourcePHPType, $sourceMediaType, $targetPHPType, $targetMediaType);

        if (is_null($result)) {
            throw new \InvalidArgumentException("Calling convert and conversion not possible");
        }

        return $result;
    }

    public function canConvert(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return !is_null($this->getConversionResult($sourceType, $sourceMediaType, $targetType, $targetMediaType));
    }

    private function getConversionResult(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        foreach ($this->convertTo as $conversion) {
            if (
                $sourceMediaType->isCompatibleWith($conversion["sourceMediaType"]) && $sourceType->isCompatibleWith($conversion["sourceType"])
                &&
                $targetMediaType->isCompatibleWith($conversion["targetMediaType"]) && $targetType->isCompatibleWith($conversion["targetType"])
            ) {
                return $conversion["result"];
            }
        }

        return null;
    }
}