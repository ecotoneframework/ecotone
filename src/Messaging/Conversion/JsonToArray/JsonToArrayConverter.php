<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion\JsonToArray;
use SimplyCodedSoftware\Messaging\Conversion\Converter;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;

/**
 * Class JsonToArrayConverter
 * @package SimplyCodedSoftware\Messaging\Conversion\JsonToArray
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class JsonToArrayConverter implements Converter
{
    /**
     * @inheritDoc
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        return \json_decode($source, true);
    }

    /**
     * @inheritDoc
     */
    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return
            $sourceType->equals(TypeDescriptor::createString())
            && $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_JSON)
            && $targetType->equals(TypeDescriptor::createArray())
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP_OBJECT);
    }
}