<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Conversion\StringToUuid;
use Ecotone\Messaging\Handler\Type;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * Class StringToUuidConverter
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StringToUuidConverter implements Converter
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
        return $sourceType->isString() && $targetType->isClassOfType(UuidInterface::class);
    }
}