<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Type;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class UnionTypeDescriptor
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class UnionType extends Type implements DefinedObject
{
    /**
     * @param Type[] $typeDescriptors
     */
    public function __construct(private readonly array $typeDescriptors)
    {
        if (count($typeDescriptors) < 2) {
            throw InvalidArgumentException::create('Union type must consist of at least 2 types');
        }
    }

    /**
     * @param Type[] $types
     * @return UnionType
     */
    public static function createWith(array $types): Type
    {
        usort($types, fn (Type $a, Type $b): int => (string) $a <=> (string) $b);
        $types = array_values(array_unique($types));

        if (count($types) === 1) {
            return array_values($types)[0];
        }

        return new self($types);
    }

    public function withoutNull(): Type
    {
        $newTypes = [];
        foreach ($this->typeDescriptors as $typeDescriptor) {
            if (! $typeDescriptor->isIdentifiedBy(TypeIdentifier::NULL)) {
                $newTypes[] = $typeDescriptor;
            }
        }
        return self::createWith($newTypes);
    }

    /**
     * @param Type[] $types
     * @return self
     */
    public function withTypes(array $types): self
    {
        return self::createWith([...$this->typeDescriptors, ...$types]);
    }

    /**
     * @inheritDoc
     */
    public function isUnionType(): bool
    {
        return true;
    }

    public function isIdentifiedBy(string|TypeIdentifier ...$identifiers): bool
    {
        foreach ($this->typeDescriptors as $typeDescriptor) {
            if ($typeDescriptor->isIdentifiedBy(...$identifiers)) {
                return true;
            }
        }

        return false;
    }

    public function acceptType(Type $otherType): bool
    {
        foreach ($this->typeDescriptors as $typeDescriptor) {
            if ($typeDescriptor->acceptType($otherType)) {
                return true;
            }
        }

        return false;
    }

    public function accepts(mixed $value): bool
    {
        foreach ($this->typeDescriptors as $typeDescriptor) {
            if ($typeDescriptor->accepts($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function equals(Type $toCompare): bool
    {
        if (! $toCompare instanceof self) {
            return false;
        }

        if (count($this->typeDescriptors) !== count($toCompare->getUnionTypes())) {
            return false;
        }

        foreach ($this->typeDescriptors as $typeDescriptor) {
            if (! in_array($typeDescriptor->toString(), $toCompare->getUnionTypes())) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Type[]
     */
    public function getUnionTypes(): array
    {
        return $this->typeDescriptors;
    }

    public function containsType(Type $targetType): bool
    {
        foreach ($this->typeDescriptors as $typeDescriptor) {
            if ($typeDescriptor->equals($targetType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isCollection(): bool
    {
        foreach ($this->typeDescriptors as $ownedTypeDescriptor) {
            if ($ownedTypeDescriptor->isCollection()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isArrayButNotClassBasedCollection(): bool
    {
        foreach ($this->typeDescriptors as $ownedTypeDescriptor) {
            if ($ownedTypeDescriptor->isArrayButNotClassBasedCollection()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isClassOrInterface(): bool
    {
        foreach ($this->typeDescriptors as $typeDescriptor) {
            if ($typeDescriptor->isClassOrInterface()) {
                return true;
            }
        }

        return false;
    }

    public function isClassNotInterface(): bool
    {
        foreach ($this->typeDescriptors as $typeDescriptor) {
            if ($typeDescriptor->isClassNotInterface()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isCompoundObjectType(): bool
    {
        foreach ($this->typeDescriptors as $ownedTypeDescriptor) {
            if ($ownedTypeDescriptor->isCompoundObjectType()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isInterface(): bool
    {
        foreach ($this->typeDescriptors as $ownedTypeDescriptor) {
            if ($ownedTypeDescriptor->isInterface()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return implode('|', array_map(fn (Type $typeDescriptor) => $typeDescriptor->toString(), $this->typeDescriptors));
    }

    /**
     * @inheritDoc
     */
    public function isAttribute(): bool
    {
        foreach ($this->typeDescriptors as $typeDescriptor) {
            if ($typeDescriptor->isAttribute()) {
                return true;
            }
        }

        return false;
    }

    public function isEnum(): bool
    {
        foreach ($this->typeDescriptors as $typeDescriptor) {
            if ($typeDescriptor->isEnum()) {
                return true;
            }
        }

        return false;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->typeDescriptors]);
    }
}
