<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Converter;

use Ecotone\Messaging\Attribute\MediaTypeConverter;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * Class ExampleMediaTypeConverter
 * @package Test\Ecotone\Messaging\Fixture\Annotation\Converter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ExceptionalConverter implements Converter
{
    /**
     * @inheritDoc
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType)
    {
        throw new \InvalidArgumentException("Converter should not be called");
    }

    /**
     * @inheritDoc
     */
    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool
    {
        return true;
    }
}