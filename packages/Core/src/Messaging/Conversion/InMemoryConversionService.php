<?php


namespace Ecotone\Messaging\Conversion;


use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Event\TicketWasRegistered;

class InMemoryConversionService implements ConversionService
{
    private array $convertTo;

    private function __construct(array $convertTo)
    {
        $this->convertTo = $convertTo;
    }

    public static function createWithConversion(mixed $dataToConvert, string $sourceMediaType, string $sourceType, string $targetMediaType, string $targetType, $conversionResult) : self
    {
        return new self([
            [
                "dataToConvert" => $dataToConvert,
                "sourceMediaType" => MediaType::parseMediaType($sourceMediaType),
                "sourceType" => TypeDescriptor::create($sourceType),
                "targetMediaType" => MediaType::parseMediaType($targetMediaType),
                "targetType" => TypeDescriptor::create($targetType),
                "result" => $conversionResult
            ],
            [
                "dataToConvert" => $conversionResult,
                "sourceMediaType" => MediaType::parseMediaType($targetMediaType),
                "sourceType" => TypeDescriptor::create($targetType),
                "targetMediaType" => MediaType::parseMediaType($sourceMediaType),
                "targetType" => TypeDescriptor::create($sourceType),
                "result" => $dataToConvert
            ]
        ]);
    }

    public function registerInPHPConversion(mixed $dataToConvert, mixed $conversionResult) : static
    {
        return $this->registerConversion($dataToConvert, MediaType::APPLICATION_X_PHP, TypeDescriptor::createFromVariable($dataToConvert), MediaType::APPLICATION_X_PHP, TypeDescriptor::createFromVariable($conversionResult), $conversionResult);
    }

    public function registerConversion(mixed $dataToConvert, string $sourceMediaType, string $sourceType, string $targetMediaType, string $targetType, $conversionResult) : static
    {
        $this->convertTo[] =
            [
                "dataToConvert" => $dataToConvert,
                "sourceMediaType" => MediaType::parseMediaType($sourceMediaType),
                "sourceType" => TypeDescriptor::create($sourceType),
                "targetMediaType" => MediaType::parseMediaType($targetMediaType),
                "targetType" => TypeDescriptor::create($targetType),
                "result" => $conversionResult
            ];

        return $this;
    }

    public static function createWithoutConversion() : self
    {
        return new self([]);
    }

    public function convert($source, Type $sourcePHPType, MediaType $sourceMediaType, Type $targetPHPType, MediaType $targetMediaType)
    {
        if (!$this->canConvert($sourcePHPType, $sourceMediaType, $targetPHPType, $targetMediaType)) {
            throw new \RuntimeException("Can't convert");
        }

        $result = $this->getConversionResult($source, $sourcePHPType, $sourceMediaType, $targetPHPType, $targetMediaType);

        if (is_null($result)) {
            throw new \InvalidArgumentException("Lack of converter for conversion from {$sourceMediaType}:{$sourcePHPType} to {$targetMediaType}:{$targetPHPType}");
        }

        return $result;
    }

    public function canConvert(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        foreach ($this->convertTo as $conversion) {
            if (
                $sourceMediaType->isCompatibleWith($conversion["sourceMediaType"]) && $sourceType->isCompatibleWith($conversion["sourceType"])
                &&
                $targetMediaType->isCompatibleWith($conversion["targetMediaType"]) && $targetType->isCompatibleWith($conversion["targetType"])
            ) {
                return true;
            }
        }

        return false;
    }

    private function getConversionResult(mixed $dataToConvert, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        foreach ($this->convertTo as $conversion) {
            if (
                $sourceMediaType->isCompatibleWith($conversion["sourceMediaType"]) && $sourceType->isCompatibleWith($conversion["sourceType"])
                &&
                $targetMediaType->isCompatibleWith($conversion["targetMediaType"]) && $targetType->isCompatibleWith($conversion["targetType"])
                &&
                $dataToConvert == $conversion["dataToConvert"]
            ) {
                return $conversion["result"];
            }
        }

        return null;
    }
}