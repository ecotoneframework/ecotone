<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Converter;

use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Type;
use Ramsey\Uuid\Uuid;

/**
 * licence Apache-2.0
 */
class StringToUuidClassConverter implements Converter
{
    /**
     * @inheritDoc
     */
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): \Ramsey\Uuid\UuidInterface
    {
        return Uuid::fromString($source);
    }

    /**
     * @inheritDoc
     */
    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType->isString() && $targetType->isClassOfType(Uuid::class);
    }
}
