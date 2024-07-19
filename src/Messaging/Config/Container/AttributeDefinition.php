<?php

namespace Ecotone\Messaging\Config\Container;

use ReflectionAttribute;

/**
 * licence Apache-2.0
 */
class AttributeDefinition extends Definition
{
    public static function fromReflection(ReflectionAttribute $reflectionAttribute): self
    {
        return new self(
            $reflectionAttribute->getName(),
            $reflectionAttribute->getArguments()
        );
    }

    public function instance(): object
    {
        return new $this->className(...$this->arguments);
    }
}
