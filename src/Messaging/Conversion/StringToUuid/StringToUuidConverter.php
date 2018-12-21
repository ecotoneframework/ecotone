<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion\StringToUuid;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use SimplyCodedSoftware\Messaging\Conversion\Converter;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;

/**
 * Class StringToUuidConverter
 * @package SimplyCodedSoftware\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StringToUuidConverter implements Converter
{
    /**
     * @inheritDoc
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
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