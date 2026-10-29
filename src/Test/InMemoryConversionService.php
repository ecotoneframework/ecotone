<?php

namespace Ecotone\Test;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Type;
use InvalidArgumentException;
use RuntimeException;

/**
 * licence Apache-2.0
 */
class InMemoryConversionService implements ConversionService, Converter, CompilableBuilder
{
    private array $convertTo;

    private function __construct(array $convertTo)
    {
        $this->convertTo = $convertTo;
    }

    public static function createWithConversion(mixed $dataToConvert, string|MediaType $sourceMediaType, string $sourceType, string|MediaType $targetMediaType, string $targetType, $conversionResult): self
    {
        return new self([
            [
                'dataToConvert' => $dataToConvert,
                'sourceMediaType' => MediaType::parseMediaType((string)$sourceMediaType),
                'sourceType' => Type::create($sourceType),
                'targetMediaType' => MediaType::parseMediaType((string)$targetMediaType),
                'targetType' => Type::create($targetType),
                'result' => $conversionResult,
            ],
            [
                'dataToConvert' => $conversionResult,
                'sourceMediaType' => MediaType::parseMediaType((string)$targetMediaType),
                'sourceType' => Type::create($targetType),
                'targetMediaType' => MediaType::parseMediaType((string)$sourceMediaType),
                'targetType' => Type::create($sourceType),
                'result' => $dataToConvert,
            ],
        ]);
    }

    public function registerInPHPConversion(mixed $dataToConvert, mixed $conversionResult): static
    {
        return $this->registerConversion($dataToConvert, MediaType::APPLICATION_X_PHP, Type::createFromVariable($dataToConvert), MediaType::APPLICATION_X_PHP, Type::createFromVariable($conversionResult), $conversionResult);
    }

    public function registerConversion(mixed $dataToConvert, string $sourceMediaType, string $sourceType, string $targetMediaType, string $targetType, $conversionResult): static
    {
        $this->convertTo[] =
            [
                'dataToConvert' => $dataToConvert,
                'sourceMediaType' => MediaType::parseMediaType($sourceMediaType),
                'sourceType' => Type::create($sourceType),
                'targetMediaType' => MediaType::parseMediaType($targetMediaType),
                'targetType' => Type::create($targetType),
                'result' => $conversionResult,
            ];

        return $this;
    }

    public static function createWithoutConversion(): self
    {
        return new self([]);
    }

    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        if (! $this->canConvert($sourceType, $sourceMediaType, $targetType, $targetMediaType)) {
            throw new RuntimeException("Can't convert");
        }

        $result = $this->getConversionResult($source, $sourceType, $sourceMediaType, $targetType, $targetMediaType);

        if (is_null($result)) {
            throw new InvalidArgumentException("Lack of converter for conversion from {$sourceMediaType}:{$sourceType} to {$targetMediaType}:{$targetType}");
        }

        return $result;
    }

    public function canConvert(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        foreach ($this->convertTo as $conversion) {
            if (
                $sourceMediaType->isCompatibleWith($conversion['sourceMediaType']) && $sourceType->isCompatibleWith($conversion['sourceType'])
                &&
                $targetMediaType->isCompatibleWith($conversion['targetMediaType']) && $targetType->isCompatibleWith($conversion['targetType'])
            ) {
                return true;
            }
        }

        return false;
    }

    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $this->canConvert($sourceType, $sourceMediaType, $targetType, $targetMediaType);
    }

    public static function fromSerialized(string $serialized): self
    {
        return unserialize($serialized);
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        return new Definition(
            self::class,
            [
                serialize($this),
            ],
            'fromSerialized'
        );
    }

    private function getConversionResult(mixed $dataToConvert, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        foreach ($this->convertTo as $conversion) {
            if (
                $sourceMediaType->isCompatibleWith($conversion['sourceMediaType']) && $sourceType->isCompatibleWith($conversion['sourceType'])
                &&
                $targetMediaType->isCompatibleWith($conversion['targetMediaType']) && $targetType->isCompatibleWith($conversion['targetType'])
                &&
                $dataToConvert == $conversion['dataToConvert']
            ) {
                return $conversion['result'];
            }
        }
    }
}
