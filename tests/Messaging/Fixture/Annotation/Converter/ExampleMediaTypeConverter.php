<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Converter;

use Ecotone\Messaging\Annotation\MediaTypeConverter;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;

#[MediaTypeConverter]
class ExampleMediaTypeConverter implements Converter
{
    /**
     * @inheritDoc
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_JSON)
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP);
    }
}