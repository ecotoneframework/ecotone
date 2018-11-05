<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Conversion\JsonToArray;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\Converter;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\MediaType;
use SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDescriptor;

/**
 * Class JsonToArrayConverter
 * @package SimplyCodedSoftware\IntegrationMessaging\Conversion\JsonToArray
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