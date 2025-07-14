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

    public static function fromObject(object $attribute): self
    {
        return DefinitionHelper::buildAttributeDefinitionFromInstance($attribute);
    }

    public function instance(): object
    {
        if ($this->hasFactory()) {
            return DefinitionHelper::unserializeSerializedObject($this->arguments[0]);
        }

        return new $this->className(...$this->arguments);
    }
}
