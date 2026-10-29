<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\Handler\Type;

/**
 * licence Apache-2.0
 */
class StaticCallConverter extends CustomConverter
{
    public function __construct(private string $classname, private string $method, Type $sourceType, Type $targetType)
    {
        parent::__construct($sourceType, $targetType);
    }

    /**
     * @inheritDoc
     */
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        return $this->classname::{$this->method}($source);
    }
}
