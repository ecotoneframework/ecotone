<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\MessagingException;

/**
 * Class UnionTypeDescriptor
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class UnionTypeDescriptor implements Type, DefinedObject
{
    /**
     * @var TypeDescriptor[]
     */
    private ?array $typeDescriptors;

    /**
     * UnionTypeDescriptor constructor.
     * @param TypeDescriptor[] $typeDescriptors
     * @throws MessagingException
     */
    public function __construct(array $typeDescriptors)
    {
        $this->initialize($typeDescriptors);
    }

    private function initialize(array $typeDescriptors): void
    {
        foreach ($typeDescriptors as $typeDescriptor) {
            foreach ($typeDescriptors as $typeToCompare) {
                if (
                    $this->isScalarAndResource($typeDescriptor, $typeToCompare)
                    || $this->isResourceAndCompound($typeDescriptor, $typeToCompare)
                ) {
                    throw TypeDefinitionException::create("Creating union type with incompatible types: {$typeDescriptor} is not compatible with {$typeToCompare}. Check your declaration and docblock types");
                }
            }
        }

        $this->typeDescriptors = $typeDescriptors;
    }

    /**
     * @param TypeDescriptor[] $types
     * @return UnionTypeDescriptor
     * @throws MessagingException
     */
    public static function createWith(array $types): Type
    {
        $types = array_unique($types);

        if (count($types) === 1) {
            return array_values($types)[0];
        }

        return new self($types);
    }

    /**
     * @inheritDoc
     */
    public function isUnionType(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isCompatibleWith(Type $toCompare): bool
    {
        foreach ($this->typeDescriptors as $typeDescriptor) {
            if ($typeDescriptor->isCompatibleWith($toCompare)) {
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
        if (! $toCompare->isUnionType()) {
            return false;
        }

        foreach ($this->typeDescriptors as $typeDescriptor) {
            if (! in_array($typeDescriptor->toString(), $toCompare->getUnionTypes())) {
                return false;
            }
        }

        foreach ($toCompare->getUnionTypes() as $typeDescriptor) {
            if (! in_array($typeDescriptor->toString(), $this->getUnionTypes())) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return TypeDescriptor[]
     */
    public function getUnionTypes(): array
    {
        return $this->typeDescriptors;
    }

    /**
     * @inheritDoc
     */
    public function isClassOfType(string $interfaceName): bool
    {
        foreach ($this->typeDescriptors as $ownedTypeDescriptor) {
            if ($ownedTypeDescriptor->isClassOfType($interfaceName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isIterable(): bool
    {
        foreach ($this->typeDescriptors as $ownedTypeDescriptor) {
            if ($ownedTypeDescriptor->isIterable()) {
                return true;
            }
        }

        return false;
    }

    public function isNullType(): bool
    {
        foreach ($this->typeDescriptors as $ownedTypeDescriptor) {
            if ($ownedTypeDescriptor->isNullType()) {
                return true;
            }
        }

        return false;
    }

    public function isMessage(): bool
    {
        foreach ($this->typeDescriptors as $ownedTypeDescriptor) {
            if ($ownedTypeDescriptor->isMessage()) {
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
    public function isBoolean(): bool
    {
        foreach ($this->typeDescriptors as $ownedTypeDescriptor) {
            if ($ownedTypeDescriptor->isBoolean()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isVoid(): bool
    {
        foreach ($this->typeDescriptors as $ownedTypeDescriptor) {
            if ($ownedTypeDescriptor->isVoid()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isString(): bool
    {
        foreach ($this->typeDescriptors as $ownedTypeDescriptor) {
            if ($ownedTypeDescriptor->isString()) {
                return true;
            }
        }

        return false;
    }

    public function isInteger(): bool
    {
        foreach ($this->typeDescriptors as $ownedTypeDescriptor) {
            if ($ownedTypeDescriptor->isInteger()) {
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
    public function isPrimitive(): bool
    {
        foreach ($this->typeDescriptors as $ownedTypeDescriptor) {
            if ($ownedTypeDescriptor->isPrimitive()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isAnything(): bool
    {
        foreach ($this->typeDescriptors as $typeDescriptor) {
            if ($typeDescriptor->isAnything()) {
                return true;
            }
        }

        return false;
    }

    public function isAbstractClass(): bool
    {
        foreach ($this->typeDescriptors as $typeDescriptor) {
            if ($typeDescriptor->isAbstractClass()) {
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
    public function isScalar(): bool
    {
        foreach ($this->typeDescriptors as $typeDescriptor) {
            if ($typeDescriptor->isScalar()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        $asString = '';

        foreach ($this->typeDescriptors as $typeDescriptor) {
            $asString .= $asString ? "|{$typeDescriptor->toString()}" : $typeDescriptor->toString();
        }

        return $asString;
    }


    /**
     * @inheritDoc
     */
    public function isResource(): bool
    {
        foreach ($this->typeDescriptors as $typeDescriptor) {
            if ($typeDescriptor->isResource()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isCompoundType(): bool
    {
        foreach ($this->typeDescriptors as $typeDescriptor) {
            if ($typeDescriptor->isCompoundType()) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param TypeDescriptor $typeHint
     * @param TypeDescriptor $typeHintToCompare
     * @return bool
     */
    private function isScalarAndResource(TypeDescriptor $typeHint, TypeDescriptor $typeHintToCompare): bool
    {
        return $typeHint->isScalar() && $typeHintToCompare->isResource();
    }

    /**
     * @param TypeDescriptor $typeHint
     * @param TypeDescriptor $typeToCompare
     * @return bool
     */
    private function isResourceAndCompound(TypeDescriptor $typeHint, TypeDescriptor $typeToCompare): bool
    {
        return $typeHint->isResource() && $typeToCompare->isCompoundType() || $typeHint->isCompoundType() && $typeToCompare->isResource();
    }

    public function isEnum(): bool
    {
        return false;
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->typeDescriptors]);
    }
}
