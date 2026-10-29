<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\Handler\Type;

abstract class CustomConverter implements Converter
{
    public function __construct(private Type $sourceType, private Type $targetType)
    {
    }

    /**
     * @inheritDoc
     */
    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)
            && $sourceType->isCompatibleWith($this->sourceType)
            && (
                $targetType->equals($this->targetType)
                || ($targetType instanceof Type\UnionType && $targetType->containsType($this->targetType))
            );
    }
}
