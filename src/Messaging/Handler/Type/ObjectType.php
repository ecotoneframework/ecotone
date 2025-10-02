<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Type;

use Attribute;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use ReflectionClass;
use Traversable;

class ObjectType extends Type implements DefinedObject
{
    public readonly string $className;
    private ?bool $isAttribute;

    public function __construct(string $className, ?bool $isAttribute = null)
    {
        $this->className = ltrim($className, '\\');
        $this->isAttribute = $isAttribute;
    }

    public static function from(self|string $className): self
    {
        if ($className instanceof self) {
            return $className;
        }
        $className = trim($className);
        if (! self::itExists($className)) {
            throw TypeDefinitionException::create("Given class or interface {$className} does not exist");
        }

        return new self($className);
    }

    public function exists(): bool
    {
        return self::itExists($this->className);
    }

    private static function itExists(string $className): bool
    {
        return class_exists($className) || interface_exists($className) || enum_exists($className);
    }

    public function isIdentifiedBy(string|TypeIdentifier ...$identifiers): bool
    {
        foreach ($identifiers as $identifier) {
            if ($identifier instanceof TypeIdentifier) {
                if ($identifier === TypeIdentifier::OBJECT) {
                    return true;
                } elseif ($identifier === TypeIdentifier::ITERABLE && is_a($this->className, Traversable::class, true)) {
                    return true;
                }
            } elseif (\is_a($this->className, $identifier, true)) {
                return true;
            }
        }

        return false;
    }

    public function acceptType(Type $otherType): bool
    {
        return $otherType->isIdentifiedBy($this->className);
    }

    public function equals(Type $toCompare): bool
    {
        return $toCompare instanceof self && $toCompare->className === $this->className;
    }

    public function accepts(mixed $value): bool
    {
        return $value instanceof $this->className;
    }

    public function __toString(): string
    {
        return ltrim($this->className, '\\');
    }

    public function isClassOrInterface(): bool
    {
        return true;
    }

    public function isInterface(): bool
    {
        return interface_exists($this->className);
    }

    public function isClassNotInterface(): bool
    {
        return class_exists($this->className);
    }

    public function isEnum(): bool
    {
        return enum_exists($this->className);
    }

    public function isAttribute(): bool
    {
        return $this->isAttribute ??= ! empty((new ReflectionClass($this->className))->getAttributes(Attribute::class));
    }

    public function __sleep(): array
    {
        $this->isAttribute();
        return ['className', 'isAttribute'];
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->className, $this->isAttribute]);
    }
}
