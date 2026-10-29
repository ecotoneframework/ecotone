<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\Handler\Type;

/**
 * Class ReferenceConverter
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class ReferenceServiceConverter extends CustomConverter
{
    public function __construct(private object $object, private string $method, Type $sourceType, Type $targetType)
    {
        parent::__construct($sourceType, $targetType);
    }

    /**
     * @inheritDoc
     */
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        return $this->object->{$this->method}($source);
    }
}
