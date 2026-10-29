<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Type;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\Type;
use InvalidArgumentException;

class GenericType extends Type implements DefinedObject
{
    /**
     * @var non-empty-list<Type>
     */
    public readonly array $genericTypes;

    public function __construct(public readonly BuiltinType|ObjectType $type, Type ...$genericTypes)
    {
        if ($type instanceof BuiltinType && ! $type->isIterable()) {
            throw new InvalidArgumentException('Only collection types can be generic');
        }
        if (empty($genericTypes)) {
            throw new InvalidArgumentException('Generic types cannot be empty');
        }
        $this->genericTypes = $genericTypes;
    }

    /**
     * In case all generics are anything, this method will return the base type
     */
    public static function from(BuiltinType|ObjectType $type, Type ...$genericTypes): Type
    {
        $allAnything = true;
        foreach ($genericTypes as $genericType) {
            if (! $genericType->isAnything()) {
                $allAnything = false;
                break;
            }
        }
        if ($allAnything) {
            return $type;
        } else {
            return new self($type, ...$genericTypes);
        }
    }

    public function isIdentifiedBy(string|TypeIdentifier ...$identifiers): bool
    {
        return $this->type->isIdentifiedBy(...$identifiers);
    }

    public function acceptType(Type $otherType): bool
    {
        if (! $this->type->acceptType($otherType)) {
            return false;
        }
        if (! $otherType instanceof GenericType) {
            return true;
        }

        if (count($this->genericTypes) !== count($otherType->genericTypes)) {
            return false;
        }
        foreach ($this->genericTypes as $index => $genericType) {
            if (! $genericType->acceptType($otherType->genericTypes[$index])) {
                return false;
            }
        }
        return true;
    }

    public function accepts(mixed $value): bool
    {
        if (! $this->type->accepts($value)) {
            return false;
        }
        if ($this->type instanceof ObjectType) {
            return true;
        }
        // iterable case
        if (empty($this->genericTypes)) {
            return true;
        }
        foreach ($value as $key => $item) {
            if (! $this->genericTypes[0]->accepts($key)) {
                return false;
            }
            if (! $this->genericTypes[1]->accepts($item)) {
                return false;
            }
        }
        return true;
    }

    public function equals(Type $toCompare): bool
    {
        if (! ($toCompare instanceof self
            && $this->type->equals($toCompare->type)
            && count($this->genericTypes) === count($toCompare->genericTypes))) {
            return false;
        }
        foreach ($this->genericTypes as $index => $genericType) {
            if (! $genericType->equals($toCompare->genericTypes[$index])) {
                return false;
            }
        }
        return true;
    }

    public function isArrayButNotClassBasedCollection(): bool
    {
        if (! $this->type->isCollection()) {
            return false;
        }

        $valueType = match (count($this->genericTypes)) {
            1 => $this->genericTypes[0],
            2 => $this->genericTypes[1],
            default => null,
        };

        if ($valueType instanceof ObjectType) {
            return false;
        }
        return true;
    }

    public function isCollection(): bool
    {
        return $this->type->isIterable() && count($this->genericTypes) === 1;
    }

    public function isClassNotInterface(): bool
    {
        return $this->type->isClassNotInterface();
    }

    public function isEnum(): bool
    {
        return $this->type->isEnum();
    }

    public function isInterface(): bool
    {
        return $this->type->isInterface();
    }

    public function isAttribute(): bool
    {
        return $this->type->isAttribute();
    }

    public function __toString(): string
    {
        $result = $this->type->__toString() . '<' . $this->genericTypes[0];
        for ($i = 1; $i < count($this->genericTypes); $i++) {
            $result .= ',' . $this->genericTypes[$i];
        }
        $result .= '>';
        return $result;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->type,
            ...$this->genericTypes,
        ]);
    }
}
