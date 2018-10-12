<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Conversion;
use SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDescriptor;

/**
 * Interface Converter
 * @package SimplyCodedSoftware\IntegrationMessaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Converter
{
    /**
     * @param mixed $source
     * @param TypeDescriptor $sourceType
     * @param TypeDescriptor $targetType
     * @param MediaType $sourceMediaType
     * @param MediaType $targetMediaType
     * @return mixed
     */
    public function convert($source, TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType);

    /**
     * @param TypeDescriptor $sourceType
     * @param TypeDescriptor $targetType
     * @param MediaType $sourceMediaType
     * @param MediaType $targetMediaType
     * @return bool
     */
    public function matches(TypeDescriptor $sourceType, MediaType $sourceMediaType, TypeDescriptor $targetType, MediaType $targetMediaType) : bool;
}