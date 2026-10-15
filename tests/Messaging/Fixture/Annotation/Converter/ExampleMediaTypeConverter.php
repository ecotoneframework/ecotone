<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Converter;

use Ecotone\Api\Attribute\MediaTypeConverter;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Handler\Type;

#[MediaTypeConverter]
/**
 * licence Apache-2.0
 */
class ExampleMediaTypeConverter implements Converter
{
    /**
     * @inheritDoc
     */
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_JSON)
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP);
    }
}
