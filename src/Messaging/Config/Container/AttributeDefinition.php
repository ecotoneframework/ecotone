<?php

namespace Ecotone\Messaging\Config\Container;

use function call_user_func_array;

use ReflectionAttribute;

/**
 * licence Apache-2.0
 */
class AttributeDefinition extends Definition
{
    public function __construct(string $className, array $arguments = [], string|array $factory = '', private ?AttributeDeclaration $declaration = null)
    {
        parent::__construct($className, $arguments, $factory);
    }

    public static function fromReflection(ReflectionAttribute $reflectionAttribute, ?AttributeDeclaration $declaration = null): self
    {
        return new self(
            $reflectionAttribute->getName(),
            $reflectionAttribute->getArguments(),
            '',
            $declaration,
        );
    }

    public static function fromObject(object $attribute, ?AttributeDeclaration $declaration = null): self
    {
        return DefinitionHelper::buildAttributeDefinitionFromInstance($attribute, $declaration);
    }

    public function getDeclaration(): ?AttributeDeclaration
    {
        return $this->declaration;
    }

    public function instance(): object
    {
        if ($this->hasFactory()) {
            return call_user_func_array($this->getFactory(), $this->arguments);
        }

        return new $this->className(...$this->arguments);
    }
}
