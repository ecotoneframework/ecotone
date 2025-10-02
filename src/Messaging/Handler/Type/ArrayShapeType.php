<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Type;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\Type;

/**
 * Represents an array shape type like array{name: string, age: int}
 */
class ArrayShapeType extends Type implements DefinedObject
{
    /**
     * @param array<string, Type> $shape Array of field name => type pairs
     */
    public function __construct(public readonly array $shape)
    {
    }

    public function isIdentifiedBy(string|TypeIdentifier ...$identifiers): bool
    {
        return in_array(TypeIdentifier::ARRAY, $identifiers, true);
    }

    public function acceptType(Type $otherType): bool
    {
        // ArrayShape accepts other ArrayShape types if they have compatible structure
        if ($otherType instanceof self) {
            return $this->acceptShape($otherType);
        }

        // ArrayShape accepts array types
        if ($otherType->isIdentifiedBy(TypeIdentifier::ARRAY)) {
            return true;
        }

        return false;
    }

    public function accepts(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        // Check if the array has the required structure
        foreach ($this->shape as $fieldName => $fieldType) {
            if (! array_key_exists($fieldName, $value)) {
                return false;
            }

            if (! $fieldType->accepts($value[$fieldName])) {
                return false;
            }
        }

        return true;
    }

    public function equals(Type $toCompare): bool
    {
        if (! $toCompare instanceof self) {
            return false;
        }

        if (count($this->shape) !== count($toCompare->shape)) {
            return false;
        }

        foreach ($this->shape as $fieldName => $fieldType) {
            if (! array_key_exists($fieldName, $toCompare->shape)) {
                return false;
            }

            if (! $fieldType->equals($toCompare->shape[$fieldName])) {
                return false;
            }
        }

        return true;
    }

    public function __toString(): string
    {
        $fields = [];
        foreach ($this->shape as $fieldName => $fieldType) {
            $fields[] = $fieldName . ': ' . $fieldType->toString();
        }

        return 'array{' . implode(', ', $fields) . '}';
    }

    /**
     * Check if this array shape is compatible with another array shape
     */
    private function acceptShape(self $other): bool
    {
        // This shape is compatible if it has all the required fields of the other shape
        foreach ($this->shape as $fieldName => $fieldType) {
            if (! array_key_exists($fieldName, $other->shape)) {
                return false;
            }

            if (! $this->shape[$fieldName]->acceptType($fieldType)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the shape definition
     * @return array<string, Type>
     */
    public function getShape(): array
    {
        return $this->shape;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->shape]);
    }
}
