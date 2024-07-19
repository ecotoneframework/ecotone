<?php

namespace Ecotone\AnnotationFinder;

use InvalidArgumentException;

/**
 * licence Apache-2.0
 */
class ConfigurationException extends InvalidArgumentException
{
    public static function create(string $message): self
    {
        return new self($message);
    }
}
