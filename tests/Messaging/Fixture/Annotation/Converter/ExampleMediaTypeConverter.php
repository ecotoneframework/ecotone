<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Converter;

use SimplyCodedSoftware\Messaging\Annotation\MediaTypeConverter;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Conversion\Converter;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;

/**
 * Class ExampleMediaTypeConverter
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Converter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MediaTypeConverter()
 */
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
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP_OBJECT);
    }
}