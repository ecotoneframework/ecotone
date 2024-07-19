<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\HeaderConversion;

use Ecotone\Messaging\Attribute\MediaTypeConverter;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\Assert;

#[MediaTypeConverter]
/**
 * licence Apache-2.0
 */
final class JsonConverter implements Converter
{
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        Assert::isTrue($sourceType->isString() || $sourceType->isIterable(), "Json converter can only convert string or array, given {$sourceType}");

        if ($targetMediaType->isCompatibleWith(MediaType::createApplicationXPHP())) {
            return json_decode($source, true, 512, JSON_THROW_ON_ERROR);
        }

        return json_encode($source, JSON_THROW_ON_ERROR);
    }

    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return $targetMediaType->isCompatibleWith(MediaType::createApplicationJson()) || $sourceMediaType->isCompatibleWith(MediaType::createApplicationJson());
    }
}
