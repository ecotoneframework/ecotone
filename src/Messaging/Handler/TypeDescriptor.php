<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

/**
 * Class TypeDescriptor
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class TypeDescriptor implements Type, DefinedObject
{
    public const COLLECTION_TYPE_REGEX = "/[a-zA-Z0-9]*<([\\a-zA-Z0-9,\s]*)>/";
    public const STRUCTURED_COLLECTION_ARRAY_TYPE = '/[a-zA-Z0-9]*<([\\a-zA-Z0-9,\s\<\>\{\}]*)>/';
    public const STRUCTURED_ARRAY_TYPE = '/^array\{.*\}$/';
    private const COLLECTION_TYPE_SPLIT_REGEX = '/(?:[^,<>()]+|<[^<>]*(?:<(?:[^<>]+)>)?[^<>]*>)+/';

    //    scalar types
    public const         INTEGER = 'int';
    private const INTEGER_LONG_NAME = 'integer';
    public const         FLOAT = 'float';
    private const DOUBLE = 'double';
    public const         BOOL = 'bool';
    private const BOOL_LONG_NAME = 'boolean';
    private const BOOL_FALSE_NAME = 'false';
    private const BOOL_TRUE_NAME = 'true';
    public const         STRING = 'string';

    //    compound types
    public const ARRAY = 'array';
    public const         ITERABLE = 'iterable';
    public const CLOSURE = 'Closure';
    public const CALLABLE = 'callable';
    public const         OBJECT = 'object';

    //    resource
    public const RESOURCE = 'resource';

    public const ANYTHING = 'anything';
    public const VOID = 'void';

    public const MIXED = 'mixed';
    public const NULL = 'null';

    private static array $cache = [];

    private string $type;

    /**
     * @return string[]
     */
    private static function resolveCollectionTypes(string $foundCollectionTypes): array
    {
        preg_match_all(self::COLLECTION_TYPE_SPLIT_REGEX, $foundCollectionTypes, $match);
        $collectionTypes = $match[0];

        $resolvedTypes = [];
        foreach ($collectionTypes as $collectionType) {
            /** Collection in collection */
            if (preg_match(self::COLLECTION_TYPE_REGEX, $collectionType)) {
                $resolvedTypes[] = TypeDescriptor::create($collectionType)->toString();
            } else {
                $resolvedTypes[] = self::removeSlashPrefix($collectionType);
            }
        }

        return $resolvedTypes;
    }

    /**
     * @return bool
     */
    public function isCompoundType(): bool
    {
        return in_array($this->type, [self::ARRAY, self::ITERABLE, self::CLOSURE, self::OBJECT]);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isItTypeOfScalar(string $type): bool
    {
        return in_array($type, [self::INTEGER, self::FLOAT, self::BOOL, self::STRING, self::INTEGER_LONG_NAME, self::DOUBLE, self::BOOL_LONG_NAME, self::BOOL_FALSE_NAME, self::BOOL_TRUE_NAME]);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isItTypeOfPrimitive(string $type): bool
    {
        return self::isItTypeOfCompoundType($type) || self::isItTypeOfScalar($type) || $type === self::RESOURCE;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isItTypeOfCollection(string $type): bool
    {
        return (bool)preg_match(self::COLLECTION_TYPE_REGEX, $type);
    }

    private static function isStructuredArrayType(string $type): bool
    {
        return (bool)preg_match(self::STRUCTURED_COLLECTION_ARRAY_TYPE, $type) || (bool)preg_match(self::STRUCTURED_ARRAY_TYPE, $type);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isItTypeOfResource(string $type): bool
    {
        return $type == self::RESOURCE;
    }

    /**
     * @param string $typeToCompare
     *
     * @return bool
     */
    public static function isItTypeOfVoid(string $typeToCompare): bool
    {
        return $typeToCompare === self::VOID;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isMixedType(string $type): bool
    {
        return $type === self::MIXED;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isNull(string $type): bool
    {
        return $type === self::NULL;
    }

    /**
     * @param TypeDescriptor $toCompare
     *
     * @return bool
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     */
    public function isCompatibleWith(Type $toCompare): bool
    {
        if ($toCompare instanceof UnionTypeDescriptor) {
            foreach ($toCompare->getUnionTypes() as $unionType) {
                if ($this->isCompatibleWith($unionType)) {
                    return true;
                }
            }

            return false;
        }

        if ($this->isNullType() && $toCompare->isNullType()) {
            return true;
        }

        if (is_a($this->type, $toCompare->getTypeHint(), true)) {
            return true;
        }

        if ($this->isAnything() || $toCompare->isAnything()) {
            return true;
        }

        if ($this->isScalar() && ! $toCompare->isScalar()) {
            return false;
        }

        if (! $this->isScalar() && $toCompare->isScalar()) {
            if ($this->isClassOrInterface()) {
                if ($this->isCompoundObjectType()) {
                    return false;
                }

                $toCompareClass = new ReflectionClass($this->getTypeHint());

                if ($toCompare->isString() && $toCompareClass->hasMethod('__toString')) {
                    return true;
                }
            }

            return false;
        }

        if (($this->isClassOrInterface() && ! $toCompare->isClassOrInterface()) || ($toCompare->isClassOrInterface() && ! $this->isClassOrInterface())) {
            return false;
        }

        if ($this->isCollection() && $toCompare->isCollection()) {
            $thisGenericTypes = $this->resolveGenericTypes();
            $comparedGenericTypes = $toCompare->resolveGenericTypes();

            if (count($thisGenericTypes) !== count($comparedGenericTypes)) {
                return false;
            }

            for ($index = 0; $index < count($thisGenericTypes); $index++) {
                if (! $thisGenericTypes[$index]->equals($comparedGenericTypes[$index])) {
                    return false;
                }
            }

            return true;
        }

        if ($this->isClassOrInterface() && $toCompare->isClassOrInterface()) {
            if (! $this->equals($toCompare)) {
                if (! $this->isCompoundObjectType() && $toCompare->isCompoundObjectType()) {
                    return true;
                }
                if ($this->isCompoundObjectType() && ! $toCompare->isCompoundObjectType()) {
                    return false;
                }

                $thisClass = new ReflectionClass($this->getTypeHint());
                $toCompareClass = new ReflectionClass($toCompare->getTypeHint());

                if ($thisClass->isInterface() && ! $toCompareClass->isInterface()) {
                    return $toCompareClass->implementsInterface($this->getTypeHint());
                }
                if ($toCompareClass->isInterface() && ! $thisClass->isInterface()) {
                    return $thisClass->implementsInterface($toCompare->getTypeHint());
                }

                if ($thisClass->isInterface() && $toCompareClass->isInterface()) {
                    if ($thisClass->implementsInterface($toCompare->getTypeHint())) {
                        return true;
                    }
                }

                return $thisClass->isSubclassOf($toCompare->getTypeHint());
            }
        }

        if ($this->isVoid() && $toCompare->isVoid()) {
            return false;
        }

        return true;
    }

    /**
     * @param string $typeHint
     *
     * @return bool
     */
    public static function isItTypeOfExistingClassOrInterface(string $typeHint): bool
    {
        return class_exists($typeHint) || interface_exists($typeHint);
    }

    private static function initialize(?string $typeHint, ?string $docBlockTypeDescription = null): Type
    {
        $cacheKey = $typeHint . ':' . $docBlockTypeDescription;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        $resolvedType = [];
        foreach (self::resolveType($typeHint)->getUnionTypes() as $declarationType) {
            if ($declarationType->isIterable() && ! $declarationType->isCollection() && $docBlockTypeDescription) {
                try {
                    $docblockType = self::resolveType($docBlockTypeDescription);

                    if (! $docblockType->isCollection()) {
                        $resolvedType[] = $declarationType;
                        continue;
                    }

                    foreach ($docblockType->getUnionTypes() as $type) {
                        if ($type->isIterable()) {
                            $resolvedType[] = $type;
                        }
                    }
                } catch (TypeDefinitionException $exception) {
                    $resolvedType[] = $declarationType;
                }
            } else {
                $resolvedType[] = $declarationType;
            }
        }

        return self::$cache[$cacheKey] = UnionTypeDescriptor::createWith($resolvedType);
    }

    /**
     * @param string $typeHint
     *
     * @return bool
     * @throws ReflectionException
     */
    public static function isInternalClassOrInterface(string $typeHint): bool
    {
        if (! self::isItTypeOfExistingClassOrInterface($typeHint)) {
            return false;
        }

        return (new ReflectionClass($typeHint))->isInternal();
    }

    public static function isClosure(string $typeHint): bool
    {
        return in_array($typeHint, [self::CLOSURE, self::CALLABLE]);
    }

    /**
     * @param Type $toCompare
     *
     * @return bool
     */
    public function equals(Type $toCompare): bool
    {
        return $this->toString() === $toCompare->toString();
    }

    /**
     * TypeHint constructor.
     *
     * @param string $type
     *
     * @throws MessagingException
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @param $variable
     *
     * @return TypeDescriptor
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public static function createFromVariable($variable): Type
    {
        $type = strtolower(gettype($variable));

        if ($type === self::DOUBLE) {
            $type = self::FLOAT;
        } elseif ($type === self::INTEGER_LONG_NAME) {
            $type = self::INTEGER;
        } elseif ($type === self::BOOL_LONG_NAME) {
            $type = self::BOOL;
        } elseif ($type === self::ARRAY) {
            if (empty($variable)) {
                return self::initialize(self::ARRAY);
            }

            $collectionType = TypeDescriptor::createFromVariable(reset($variable))->toString();
            /** This won't be iterating over all variables for performance reasons */
            if (TypeDescriptor::createFromVariable(end($variable))->toString() !== $collectionType) {
                return self::initialize(self::ARRAY);
            }

            if (array_key_first($variable) === 0) {
                return self::initialize("array<{$collectionType}>");
            }

            return self::initialize("array<string,{$collectionType}>");
        } elseif ($type === self::ITERABLE) {
            $type = self::ITERABLE;
        } elseif ($type === self::OBJECT) {
            $type = get_class($variable);
        } elseif ($type === self::STRING) {
            $type = self::STRING;
        } elseif ($type === self::RESOURCE) {
            $type = self::RESOURCE;
        } elseif ($type === self::NULL) {
            $type = self::NULL;
        } else {
            $type = self::ANYTHING;
        }

        return self::initialize($type);
    }

    /**
     * Should be called only, if type descriptor is collection
     *
     * @return TypeDescriptor[]
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function resolveGenericTypes(): array
    {
        preg_match(self::COLLECTION_TYPE_REGEX, $this->type, $match);
        if (! isset($match[1])) {
            throw InvalidArgumentException::create("Can't resolve collection type on non collection");
        }

        return array_map(fn (string $type) => TypeDescriptor::create($type), self::resolveCollectionTypes($match[1]));
    }

    public static function createWithDocBlock(?string $type, ?string $docBlockTypeDescription): Type
    {
        return self::initialize($type, $docBlockTypeDescription);
    }

    public static function createCollection(string $className): Type
    {
        return self::initialize("array<{$className}>");
    }

    public static function create(?string $type): Type
    {
        return self::initialize($type, '');
    }

    public static function createAnythingType(): Type
    {
        return self::initialize(self::ANYTHING);
    }

    public static function createBooleanType(): Type
    {
        return self::initialize(self::BOOL);
    }

    public static function createIntegerType(): Type
    {
        return self::initialize(self::INTEGER);
    }

    public static function createArrayType(): Type
    {
        return self::initialize(self::ARRAY);
    }

    /**
     * Should be used instead of array, if array is not composed of scalar types or composition of types is unknown
     *
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public static function createIterable(): Type
    {
        return self::initialize(self::ITERABLE);
    }

    public static function createStringType(): Type
    {
        return self::initialize(self::STRING);
    }

    /**
     * @return string
     */
    public function getTypeHint(): string
    {
        return $this->type;
    }

    /**
     * @param string $interfaceName
     *
     * @return bool
     */
    public function isClassOfType(string $interfaceName): bool
    {
        return self::isItTypeOfExistingClassOrInterface($this->type) && ($this->type === $interfaceName || $this->type === '\\' . $interfaceName || is_subclass_of($this->type, $interfaceName));
    }

    /**
     * @return bool
     */
    public function isIterable(): bool
    {
        return $this->type === self::ARRAY || $this->type === self::ITERABLE || $this->isCollection();
    }

    public function isMessage(): bool
    {
        return $this->type === Message::class;
    }

    /**
     * @return TypeDescriptor[]
     */
    public function getUnionTypes(): array
    {
        return [$this];
    }

    /**
     * @return bool
     */
    public function isCollection(): bool
    {
        return self::isItTypeOfCollection($this->type);
    }

    public function isSingleTypeCollection(): bool
    {
        return count($this->resolveGenericTypes()) === 1;
    }

    /**
     * @return bool
     */
    public function isArrayButNotClassBasedCollection(): bool
    {
        if ($this->isCollection()) {
            foreach ($this->resolveGenericTypes() as $genericType) {
                if ($genericType->isClassOrInterface()) {
                    return false;
                }
            }

            return true;
        }

        return $this->type === self::ARRAY || $this->type === self::ITERABLE;
    }

    /**
     * @return bool
     */
    public function isBoolean(): bool
    {
        return $this->type === self::BOOL;
    }

    /**
     * @return bool
     */
    public function isVoid(): bool
    {
        return $this->type === self::VOID;
    }

    /**
     * @return bool
     */
    public function isString(): bool
    {
        return $this->type === self::STRING;
    }

    public function isInteger(): bool
    {
        return $this->type === self::INTEGER;
    }

    /**
     * @return bool
     */
    public function isClassOrInterface(): bool
    {
        return $this->type === self::OBJECT || class_exists($this->type) || interface_exists($this->type);
    }

    public function isClassNotInterface(): bool
    {
        return class_exists($this->type);
    }

    /**
     * @return bool
     */
    public function isCompoundObjectType(): bool
    {
        return $this->type === self::OBJECT;
    }

    /**
     * @return bool
     */
    public function isPrimitive(): bool
    {
        return self::isItTypeOfPrimitive($this->type);
    }

    /**
     * @return bool
     */
    public function isAnything(): bool
    {
        return $this->type === self::ANYTHING;
    }

    /**
     * @param string $typeToCheck
     * @return bool
     */
    private static function isResolvableType(string $typeToCheck): bool
    {
        return self::isItTypeOfPrimitive($typeToCheck) || class_exists($typeToCheck) || interface_exists($typeToCheck) || $typeToCheck == self::ANYTHING || self::isItTypeOfCollection($typeToCheck) || self::isStructuredArrayType($typeToCheck) || self::isMixedType($typeToCheck) || self::isNull($typeToCheck);
    }

    /**
     * @return bool
     */
    public function isInterface(): bool
    {
        return interface_exists($this->type);
    }

    public function isAbstractClass(): bool
    {
        if (! $this->isClassOrInterface()) {
            return false;
        }

        if ($this->isCompoundObjectType()) {
            return false;
        }

        $reflectionClass = new ReflectionClass($this->type);

        return $reflectionClass->isAbstract();
    }

    /**
     * @return bool
     */
    public function isScalar(): bool
    {
        return self::isItTypeOfScalar($this->type);
    }

    /**
     * @inheritDoc
     */
    public function isResource(): bool
    {
        return $this->type === self::RESOURCE;
    }


    /**
     * @param string|null $typeHint
     * @return Type
     * @throws MessagingException
     */
    private static function resolveType(?string $typeHint): Type
    {
        if (is_null($typeHint)) {
            return new static(self::ANYTHING);
        }
        $typeHint = trim($typeHint);
        if ($typeHint == '') {
            return new static(self::ANYTHING);
        }

        $finalTypes = [];
        foreach (explode('|', $typeHint) as $type) {
            if ($type === self::VOID) {
                $finalTypes[] = new static(self::VOID);
                continue;
            }

            if ($typeHint === self::INTEGER_LONG_NAME) {
                $finalTypes[] = new static(self::INTEGER);
                continue;
            }
            if ($typeHint === self::DOUBLE) {
                $finalTypes[] = new static(self::FLOAT);
                continue;
            }
            if (in_array($typeHint, [self::BOOL_LONG_NAME, self::BOOL_FALSE_NAME])) {
                $finalTypes[] = new static(self::BOOL);
                continue;
            }
            if ($typeHint === self::CALLABLE) {
                $finalTypes[] = new static(self::CLOSURE);
                continue;
            }

            if (strpos($type, '[]') !== false) {
                $type = 'array<' . str_replace('[]', '', $type) . '>';
            }

            if ($type === self::NULL) {
                $finalTypes[] = new static(self::NULL);
                continue;
            }
            if ($type === self::MIXED) {
                $finalTypes[] = new static(self::ANYTHING);
                continue;
            }

            if (preg_match(self::COLLECTION_TYPE_REGEX, $type, $match)) {
                $foundCollectionTypes = $match[1];
                $collectionTypes = self::resolveCollectionTypes($foundCollectionTypes);

                if (empty($collectionTypes)) {
                    $type = self::ARRAY;
                } else {
                    foreach ($collectionTypes as $collectionType) {
                        if (! self::isResolvableType($collectionType)) {
                            throw TypeDefinitionException::create("Unknown collection type in {$type}. Passed type in collection is not resolvable: {$collectionType}.");
                        }
                    }

                    $type = str_replace($foundCollectionTypes, implode(',', $collectionTypes), $match[0]);
                }
            } elseif (preg_match(self::STRUCTURED_COLLECTION_ARRAY_TYPE, $type, $match)) {
                $foundCollectionTypes = $match[1];
                $collectionTypes = self::resolveCollectionTypes($foundCollectionTypes);

                if (count($collectionTypes) <= 1) {
                    $type = self::ARRAY;
                } else {
                    $resolvedTypes = [];
                    foreach ($collectionTypes as $collectionType) {
                        try {
                            $resolvedTypes[] = self::resolveType($collectionType);
                        } catch (TypeDefinitionException $exception) {
                            throw TypeDefinitionException::create("Unknown collection type in {$type}. Passed type in collection is not resolvable: {$collectionType}.");
                        }
                    }

                    $type = str_replace($foundCollectionTypes, implode(',', $resolvedTypes), $match[0]);
                }
            } elseif (preg_match(self::STRUCTURED_ARRAY_TYPE, $type)) {
                $type = self::ARRAY;
            } else {
                if (! self::isResolvableType($type)) {
                    throw TypeDefinitionException::create("Passed type hint `{$type}` is not resolvable");
                }
            }


            $finalTypes[] = new static(self::removeSlashPrefix($type));
        }

        return UnionTypeDescriptor::createWith($finalTypes);
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return (string)$this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isItTypeOfCompoundType(string $type): bool
    {
        return in_array($type, [self::ARRAY, self::ITERABLE, self::CLOSURE, self::OBJECT]);
    }

    /**
     * @inheritDoc
     */
    public function isUnionType(): bool
    {
        return false;
    }

    public function isNullType(): bool
    {
        return $this->type === self::NULL;
    }

    /**
     * @param string $type
     * @return string
     */
    private static function removeSlashPrefix(string $type): string
    {
        if (class_exists($type) || interface_exists($type)) {
            $type = ltrim($type, '\\');
        }

        return trim($type);
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->type]);
    }
}
