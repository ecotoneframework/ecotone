<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion\EnumToScalar;

use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Type;
use ReflectionEnum;

/**
 * licence Apache-2.0
 */
class EnumToScalarConverter implements Converter
{
    /**
     * @inheritDoc
     */
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        $ref = new ReflectionEnum($sourceType->toString());

        if ($ref->isBacked()) {
            return $source->value;
        }

        return $source->name;
    }

    /**
     * @inheritDoc
     */
    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType->isEnum() && $targetType->isScalar();
    }
}
