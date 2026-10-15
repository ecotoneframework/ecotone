<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion\EnumToScalar;

use BackedEnum;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Handler\Type;

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
        return $source instanceof BackedEnum ? $source->value : $source->name;
    }

    /**
     * @inheritDoc
     */
    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType->isEnum() && $targetType->isScalar();
    }
}
