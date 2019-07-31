<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * Interface ConversionService
 * @package Ecotone\Messaging\Conversion
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConversionService
{
    const REFERENCE_NAME = "conversionService";

    /**
     * @param mixed          $source
     * @param TypeDescriptor $sourceType
     * @param MediaType      $sourceMediaType
     * @param TypeDescriptor $targetType
     * @param MediaType      $targetMediaType
     *
     * @return mixed
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType);

    /**
     * @param TypeDescriptor $sourceType
     * @param MediaType      $sourceMediaType
     * @param TypeDescriptor $targetType
     * @param MediaType      $targetMediaType
     *
     * @return bool
     */
    public function canConvert(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType): bool;
}