<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion\UuidToString;

use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\Assert;
use Ramsey\Uuid\UuidInterface;

/**
 * Class UuidToStringConverter
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class UuidToStringConverter implements Converter
{
    /**
     * @inheritDoc
     */
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): string
    {
        /** @var UuidInterface $source */
        Assert::isSubclassOf($source, UuidInterface::class, 'Passed type to String to Uuid converter is not Uuid');

        return $source->toString();
    }

    /**
     * @inheritDoc
     */
    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType->isClassOfType(UuidInterface::class)
            && $targetType->isString()
            && $targetMediaType->isCompatibleWith(MediaType::createApplicationXPHP());
    }
}
