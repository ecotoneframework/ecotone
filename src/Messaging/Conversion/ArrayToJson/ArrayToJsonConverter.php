<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion\ArrayToJson;

use SimplyCodedSoftware\Messaging\Conversion\Converter;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;

/**
 * Class ArrayToJsonConverter
 * @package SimplyCodedSoftware\Messaging\Conversion\ArrayToJson
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ArrayToJsonConverter implements Converter
{
    /**
     * @inheritDoc
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        return \json_encode($source);
    }

    /**
     * @inheritDoc
     */
    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return
            $sourceType->equals(TypeDescriptor::createArrayType())
            && $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP_OBJECT)
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_JSON);
    }
}