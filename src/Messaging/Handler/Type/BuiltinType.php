<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Type;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\Type;
use Stringable;

class BuiltinType extends Type implements DefinedObject
{
    public function __construct(public readonly TypeIdentifier $typeIdentifier)
    {
    }

    public function isIdentifiedBy(TypeIdentifier|string ...$identifiers): bool
    {
        return in_array($this->typeIdentifier, $identifiers, true);
    }

    public function acceptType(Type $otherType): bool
    {
        // Anything accepts everything and is compatible with everything
        if ($otherType->isIdentifiedBy(TypeIdentifier::ANYTHING)) {
            return true;
        }

        // Two scalers are always compatible with each other
        if ($this->typeIdentifier->isScalar() && $otherType->isIdentifiedBy(...TypeIdentifier::scalars())) {
            return true;
        }

        return match ($this->typeIdentifier) {
            TypeIdentifier::ANYTHING => true,
            TypeIdentifier::NEVER, TypeIdentifier::VOID => false,
            TypeIdentifier::INTEGER, TypeIdentifier::FLOAT => $otherType->isIdentifiedBy(TypeIdentifier::INTEGER, TypeIdentifier::FLOAT),
            TypeIdentifier::BOOL => $otherType->isIdentifiedBy(TypeIdentifier::BOOL, TypeIdentifier::TRUE, TypeIdentifier::FALSE),
            TypeIdentifier::NULL, TypeIdentifier::TRUE, TypeIdentifier::FALSE, TypeIdentifier::OBJECT, TypeIdentifier::RESOURCE, TypeIdentifier::ARRAY => $otherType->isIdentifiedBy($this->typeIdentifier),
            TypeIdentifier::ITERABLE => $otherType->isIdentifiedBy(TypeIdentifier::ARRAY, TypeIdentifier::ITERABLE),
            TypeIdentifier::STRING => $otherType->isIdentifiedBy(Stringable::class, ...TypeIdentifier::scalars()),
            TypeIdentifier::CALLABLE => $otherType->isIdentifiedBy(TypeIdentifier::CALLABLE),
        };
    }

    public function accepts(mixed $value): bool
    {
        // Compatibility note: Two scalers are always compatible with each other
        if ($this->typeIdentifier->isScalar() && is_scalar($value)) {
            return true;
        }

        return match ($this->typeIdentifier) {
            TypeIdentifier::INTEGER => is_int($value),
            TypeIdentifier::STRING => is_string($value) || $value instanceof Stringable,
            TypeIdentifier::FLOAT => is_float($value),
            TypeIdentifier::BOOL => is_bool($value),
            TypeIdentifier::TRUE => $value === true,
            TypeIdentifier::FALSE => $value === false,
            TypeIdentifier::ARRAY => is_array($value),
            TypeIdentifier::ITERABLE => is_iterable($value),
            TypeIdentifier::OBJECT => is_object($value),
            TypeIdentifier::ANYTHING => true,
            TypeIdentifier::NULL => $value === null,
            TypeIdentifier::CALLABLE => is_callable($value),
            TypeIdentifier::RESOURCE => is_resource($value),
            TypeIdentifier::VOID, TypeIdentifier::NEVER  => false,
        };
    }

    public function equals(Type $toCompare): bool
    {
        return $toCompare instanceof self && $toCompare->typeIdentifier === $this->typeIdentifier;
    }

    public function __toString(): string
    {
        return $this->typeIdentifier->value;
    }

    public function isCompoundObjectType(): bool
    {
        return $this->typeIdentifier === TypeIdentifier::OBJECT;
    }

    public function isCollection(): bool
    {
        return $this->typeIdentifier === TypeIdentifier::ARRAY || $this->typeIdentifier === TypeIdentifier::ITERABLE;
    }

    public function isArrayButNotClassBasedCollection(): bool
    {
        return $this->isCollection();
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->typeIdentifier,
        ]);
    }
}
