<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Conversion\ArrayToJson;

use SimplyCodedSoftware\IntegrationMessaging\Conversion\Converter;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\MediaType;
use SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDescriptor;

/**
 * Class ArrayToJsonConverter
 * @package SimplyCodedSoftware\IntegrationMessaging\Conversion\ArrayToJson
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
            $sourceType->equals(TypeDescriptor::createArray())
            && $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP_OBJECT)
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_JSON);
    }
}