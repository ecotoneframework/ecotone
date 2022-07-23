<?php

namespace Ecotone\AnnotationFinder;

use InvalidArgumentException;

class ConfigurationException extends InvalidArgumentException
{
    public static function create(string $message): self
    {
        return new self($message);
    }
}
