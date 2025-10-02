<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion\ScalarToEnum;

use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Type;
use ReflectionEnum;

/**
 * licence Apache-2.0
 */
class ScalarToEnumConverter implements Converter
{
    /**
     * @inheritDoc
     */
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        $ref = new ReflectionEnum($targetType->toString());

        if ($ref->isBacked()) {
            return $targetType->toString()::from($source);
        }

        return ($ref)->getCase($source)->getValue();
    }

    /**
     * @inheritDoc
     */
    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType->isScalar() && $targetType->isEnum();
    }
}
