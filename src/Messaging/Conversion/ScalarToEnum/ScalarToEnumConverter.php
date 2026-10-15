<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion\ScalarToEnum;

use BackedEnum;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Handler\Type;
use ValueError;

/**
 * licence Apache-2.0
 */
class ScalarToEnumConverter implements Converter
{
    /**
     * @inheritDoc
     */
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        if ($targetType instanceof Type\UnionType) {
            $targetType = $targetType->withoutNull();
        }

        $enumClass = $targetType->toString();
        if (is_subclass_of($enumClass, BackedEnum::class)) {
            return $enumClass::from($source);
        }

        foreach ($enumClass::cases() as $case) {
            if ($case->name === $source) {
                return $case;
            }
        }

        throw new ValueError(sprintf('"%s" is not a valid case name for enum %s', $source, $enumClass));
    }

    /**
     * @inheritDoc
     */
    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType->isScalar() && $targetType->isEnum();
    }
}
