<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Conversion\SerializedToObject;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * Class DeserializingConverter
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DeserializingConverter implements Converter
{
    /**
     * @inheritDoc
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        return unserialize($source);
    }

    /**
     * @inheritDoc
     */
    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP_SERIALIZED)
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP);
    }
}