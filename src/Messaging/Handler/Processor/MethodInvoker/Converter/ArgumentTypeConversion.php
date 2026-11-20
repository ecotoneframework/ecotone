<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Conversion\ConversionException;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\ExpressionResult;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Generator;

use function is_iterable;
use function is_scalar;
use function sprintf;

use Throwable;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ArgumentTypeConversion
{
    public function __construct(private ConversionService $conversionService)
    {
    }

    public static function definition(): Definition
    {
        return new Definition(self::class, [new Reference(ConversionService::REFERENCE_NAME)]);
    }

    public function canConvert(mixed $value, Type $parameterType): bool
    {
        return $this->mediaTypeToConvertFrom($value, Type::createFromVariable($value), $parameterType) !== null;
    }

    public function convertWhenPossible(mixed $value, Type $parameterType): mixed
    {
        if ($parameterType->accepts($value)) {
            return $value;
        }

        $valueType = Type::createFromVariable($value);
        $valueMediaType = $this->mediaTypeToConvertFrom($value, $valueType, $parameterType);
        if ($valueMediaType === null) {
            return $value;
        }

        return $this->conversionService->convert(
            source: $value,
            sourcePHPType: $valueType,
            sourceMediaType: $valueMediaType,
            targetPHPType: $parameterType,
            targetMediaType: MediaType::createApplicationXPHP(),
        );
    }

    public function convertExpressionResult(mixed $result, Type $parameterType): mixed
    {
        if ($result instanceof Generator || $parameterType->accepts($result)) {
            return $result;
        }

        if ($result === null) {
            throw ConversionException::create('The expression returned null, and the parameter does not allow null. Return a value from the expression or declare the parameter nullable.');
        }

        $resultType = Type::createFromVariable($result);
        $resultMediaType = $this->mediaTypeToConvertFrom($result, $resultType, $parameterType);
        if ($resultMediaType === null) {
            if (is_iterable($result) && $parameterType->isIterable()) {
                return $result;
            }

            throw ConversionException::create(sprintf(
                'The expression returned %s, which cannot be passed as %s. Register a #[Converter] from %s to %s, return %s from the expression, or change the parameter type.',
                ExpressionResult::describe($result),
                $parameterType,
                $resultType,
                $parameterType,
                $parameterType,
            ));
        }

        try {
            return $this->conversionService->convert(
                source: $result,
                sourcePHPType: $resultType,
                sourceMediaType: $resultMediaType,
                targetPHPType: $parameterType,
                targetMediaType: MediaType::createApplicationXPHP(),
            );
        } catch (Throwable $exception) {
            throw ConversionException::createFromPreviousException(
                sprintf('Converting the expression result from %s to %s failed: %s', $resultType, $parameterType, $exception->getMessage()),
                $exception,
            );
        }
    }

    private function mediaTypeToConvertFrom(mixed $value, Type $valueType, Type $parameterType): ?MediaType
    {
        $phpMediaType = MediaType::createApplicationXPHP();
        if ($this->conversionService->canConvert(sourceType: $valueType, sourceMediaType: $phpMediaType, targetType: $parameterType, targetMediaType: $phpMediaType)) {
            return $phpMediaType;
        }

        $fallbackMediaType = MediaType::parseMediaType(DefaultHeaderMapper::FALLBACK_HEADER_CONVERSION_MEDIA_TYPE);
        if (is_scalar($value) && $this->conversionService->canConvert(sourceType: $valueType, sourceMediaType: $fallbackMediaType, targetType: $parameterType, targetMediaType: $phpMediaType)) {
            return $fallbackMediaType;
        }

        return null;
    }
}
