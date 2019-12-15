<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\Handler\Type;

/**
 * Interface ConversionService
 * @package Ecotone\Messaging\Conversion
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConversionService
{
    const REFERENCE_NAME = "conversionService";

    /**
     * @param mixed $source
     * @param Type $sourceType
     * @param MediaType $sourceMediaType
     * @param Type $targetType
     * @param MediaType $targetMediaType
     *
     * @return mixed
     */
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType);

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