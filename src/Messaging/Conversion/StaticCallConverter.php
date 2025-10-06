<?php

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\Handler\Type;

/**
 * licence Apache-2.0
 */
class StaticCallConverter implements Converter
{
    public function __construct(private string $classname, private string $method, private Type $sourceType, private Type $targetType)
    {
    }

    /**
     * @inheritDoc
     */
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        return $this->classname::{$this->method}($source);
    }

    /**
     * @inheritDoc
     */
    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)
            && $sourceType->isCompatibleWith($this->sourceType)
            && $targetType->acceptType($this->targetType);
    }
}
