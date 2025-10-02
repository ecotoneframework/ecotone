<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Handler\Type\BuiltinType;
use Ecotone\Messaging\Handler\Type\GenericType;
use Ecotone\Messaging\Handler\Type\TypeContext;
use Ecotone\Messaging\Handler\Type\TypeFactory;
use Ecotone\Messaging\Handler\Type\TypeIdentifier;
use Ecotone\Messaging\Handler\Type\TypeParser;
use Ecotone\Messaging\Handler\Type\UnionType;
use Ecotone\Messaging\Message;

use function get_debug_type;
use function is_array;
use function is_callable;
use function is_iterable;
use function is_object;
use function is_resource;
use function is_string;

use Stringable;

/**
 * licence Apache-2.0
 */
abstract class Type implements Stringable
{
    use TypeFactory;

    //    scalar types
    /** @deprecated  */
    public const         STRING = 'string';
    /** @deprecated  */
    public const ARRAY = 'array';
    /** @deprecated  */
    public const         OBJECT = 'object';

    private static array $cachedTypes = [];

    abstract public function isIdentifiedBy(string ...$typeIdentifiers): bool;
    abstract public function acceptType(Type $otherType): bool;
    abstract public function equals(Type $toCompare): bool;
    abstract public function accepts(mixed $value): bool;

    public static function createFromVariable(mixed $variable): Type
    {
        if ($variable === null) {
            return self::null();
        }
        if ($variable === true) {
            return self::true();
        }
        if ($variable === false) {
            return self::false();
        }
        switch (get_debug_type($variable)) {
            case 'int':
                return self::int();
            case 'float':
                return self::float();
            case 'string':
                return self::string();
            default:
        }
        if (is_object($variable)) {
            return self::object($variable::class);
        }
        if (is_array($variable)) {
            // Check only the first element for performance reasons
            if (empty($variable)) {
                return self::array();
            }
            $genericTypes = [];
            $firstKey = array_key_first($variable);
            $lastKey = array_key_last($variable);
            if (is_string($lastKey) && is_string($firstKey)) {
                $genericTypes[] = self::string();
            }
            $firstValue = $variable[$firstKey];
            $lastValue = $variable[$lastKey];
            $firstValueType = self::createFromVariable($firstValue);
            $lastValueType = self::createFromVariable($lastValue);
            if ($firstValueType->equals($lastValueType)) {
                $genericTypes[] = $firstValueType;
            } else {
                $genericTypes[] = self::anything();
            }

            return GenericType::from(self::array(), ...$genericTypes);
        }
        if (is_iterable($variable)) {
            return self::iterable();
        }
        if (is_resource($variable)) {
            return self::resource();
        }
        if (is_callable($variable)) {
            return self::callable();
        }

        return self::anything();
    }

    public static function create(string $type, ?TypeContext $context = null): Type
    {
        $type = trim($type);
        if (! $context) {
            return self::$cachedTypes[$type] ??= (new TypeParser($type, $context))->parse();
        } else {
            return (new TypeParser($type, $context))->parse();
        }
    }

    public static function createWithDocBlock(?string $type, ?string $docBlockTypeDescription, ?TypeContext $context = null): Type
    {
        $type = trim($type ?? '');
        $docBlockTypeDescription = trim($docBlockTypeDescription ?? '');
        if (! empty($type)) {
            $baseType = self::create($type, $context);
        }
        if (! empty($docBlockTypeDescription)) {
            try {
                $docBlockType = self::create($docBlockTypeDescription, $context);
                if ($docBlockType->isIdentifiedBy(TypeIdentifier::ANYTHING)) {
                    // If docblock type is 'mixed' or 'anything', ignore it
                    $docBlockType = null;
                }
            } catch (TypeDefinitionException) {
                // If docblock type is invalid, ignore it
                $docBlockType = null;
            }
        }
        if (isset($baseType) && isset($docBlockType)) {
            // Simplify the docblock type to be compatible with the base type
            return self::filterType($docBlockType, $baseType);
        }

        return $docBlockType ?? $baseType ?? self::anything();
    }

    /**
     * Simplify a type by filtering out non-compatible types from unions
     *
     * @param Type $type The type to simplify
     * @param Type $filterType The type to filter by (e.g., 'array' to keep only array types)
     * @return Type The simplified type
     */
    private static function filterType(Type $type, Type $filterType): Type
    {
        if ($type instanceof UnionType) {
            $compatibleTypes = [];
            $unionTypes = $type->getUnionTypes();

            foreach ($unionTypes as $unionType) {
                if ($unionType->isCompatibleWith($filterType)) {
                    $compatibleTypes[] = $unionType;
                }
            }

            if (empty($compatibleTypes)) {
                // If no compatible types found, return the filter type itself
                return $filterType;
            } elseif (count($compatibleTypes) === 1) {
                // If only one compatible type, return it directly
                return $compatibleTypes[0];
            } else {
                // Return a new union with only compatible types
                return new UnionType($compatibleTypes);
            }
        }

        // For non-union types, check if they're compatible
        if ($type->isCompatibleWith($filterType)) {
            return $type;
        } else {
            return $filterType;
        }
    }

    public static function createCollection(string $className): Type
    {
        return new GenericType(self::array(), Type::object($className));
    }

    final public function nullable(): UnionType
    {
        if ($this instanceof UnionType) {
            return $this->withTypes([new BuiltinType(TypeIdentifier::NULL)]);
        }
        return UnionType::createWith([$this, new BuiltinType(TypeIdentifier::NULL)]);
    }
    final public function isCompatibleWith(Type $toCompare): bool
    {
        return $toCompare->acceptType($this);
    }

    final public function isScalar(): bool
    {
        return $this->isIdentifiedBy(...TypeIdentifier::scalars());
    }

    final public function isClassOfType(string $interfaceName): bool
    {
        return $this->isIdentifiedBy($interfaceName);
    }

    final public function isCompoundType(): bool
    {
        return $this->isIdentifiedBy(TypeIdentifier::OBJECT);
    }

    final public function isResource(): bool
    {
        return $this->isIdentifiedBy(TypeIdentifier::RESOURCE);
    }

    final public function isIterable(): bool
    {
        return $this->isIdentifiedBy(TypeIdentifier::ITERABLE, TypeIdentifier::ARRAY);
    }

    final public function isMessage(): bool
    {
        return $this->isIdentifiedBy(Message::class);
    }

    final public function isBoolean(): bool
    {
        return $this->isIdentifiedBy(TypeIdentifier::BOOL);
    }

    final public function isVoid(): bool
    {
        return $this->isIdentifiedBy(TypeIdentifier::VOID);
    }

    final public function isString(): bool
    {
        return $this->isIdentifiedBy(TypeIdentifier::STRING);
    }

    final public function isInteger(): bool
    {
        return $this->isIdentifiedBy(TypeIdentifier::INTEGER);
    }

    final public function isAnything(): bool
    {
        return $this->isIdentifiedBy(TypeIdentifier::ANYTHING);
    }

    final public function isNullType(): bool
    {
        return $this->isIdentifiedBy(TypeIdentifier::NULL);
    }

    final public function toString(): string
    {
        return (string) $this;
    }

    final public function getTypeHint(): string
    {
        return (string) $this;
    }

    ///
    /// Default implementations
    ///

    public function getUnionTypes(): array
    {
        return [$this];
    }

    public function isAttribute(): bool
    {
        return false;
    }

    public function isCollection(): bool
    {
        return false;
    }

    public function isArrayButNotClassBasedCollection(): bool
    {
        return false;
    }

    public function isClassOrInterface(): bool
    {
        return false;
    }

    public function isClassNotInterface(): bool
    {
        return false;
    }

    public function isCompoundObjectType(): bool
    {
        return false;
    }

    public function isInterface(): bool
    {
        return false;
    }

    public function isUnionType(): bool
    {
        return false;
    }

    public function isEnum(): bool
    {
        return false;
    }

    public function withoutNull(): Type
    {
        return $this;
    }
}
