<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion\SerializedToObject;

use Ecotone\Messaging\Conversion\ConversionException;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Type;

/**
 * Class DeserializingConverter
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class DeserializingConverter implements Converter
{
    /**
     * @inheritDoc
     */
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType, ?ConversionService $conversionService = null)
    {
        $phpVar = unserialize(stripslashes($source));
        if (! $targetType->accepts($phpVar)) {
            if ($conversionService === null) {
                throw ConversionException::create('To convert serialized data to different type than original, you need to set conversion service in ' . self::class);
            }
            return $conversionService->convert($phpVar, Type::createFromVariable($phpVar), MediaType::createApplicationXPHP(), $targetType, MediaType::createApplicationXPHP());
        }

        return $phpVar;
    }

    /**
     * @inheritDoc
     */
    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP_SERIALIZED)
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP);
    }
}
