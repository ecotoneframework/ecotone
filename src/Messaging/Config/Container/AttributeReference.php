<?php

namespace Ecotone\Messaging\Config\Container;

/**
 * licence Apache-2.0
 */
class AttributeReference extends Reference
{
    public function __construct(private string $attributeClass, private string $className, private ?string $methodName = null)
    {
        $annotatedEntity = $methodName ? "{$className}::{$methodName}" : $className;
        parent::__construct("attribute.{$annotatedEntity}#{$attributeClass}");
    }

    public function getAttributeClass(): string
    {
        return $this->attributeClass;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): ?string
    {
        return $this->methodName;
    }
}
