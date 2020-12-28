<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Converter;

use Ecotone\Messaging\Handler\Type;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;

class StringToUuidClassConverter implements Converter
{
    /**
     * @inheritDoc
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): \Ramsey\Uuid\UuidInterface
    {
        return Uuid::fromString($source);
    }

    /**
     * @inheritDoc
     */
    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType->isString() && $targetType->isClassOfType(Uuid::class);
    }
}