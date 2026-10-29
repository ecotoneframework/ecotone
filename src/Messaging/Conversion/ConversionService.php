<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\Handler\Type;

/**
 * Interface ConversionService
 * @package Ecotone\Messaging\Conversion
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface ConversionService
{
    public const REFERENCE_NAME = self::class;

    /**
     * @param mixed $source
     * @param Type $sourcePHPType
     * @param MediaType $sourceMediaType
     * @param Type $targetPHPType
     * @param MediaType $targetMediaType
     *
     * @return mixed
     */
    public function convert($source, Type $sourcePHPType, MediaType $sourceMediaType, Type $targetPHPType, MediaType $targetMediaType);

    /**
     * @param Type $sourceType
     * @param MediaType $sourceMediaType
     * @param Type $targetType
     * @param MediaType $targetMediaType
     *
     * @return bool
     */
    public function canConvert(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool;
}
