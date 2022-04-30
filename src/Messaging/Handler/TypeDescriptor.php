<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class TypeDescriptor
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class TypeDescriptor implements Type
{
    const COLLECTION_TYPE_REGEX = "/[a-zA-Z0-9]*<([\\a-zA-Z0-9,\s]*)>/";

    //    scalar types
    const         INTEGER           = "int";
    private const INTEGER_LONG_NAME = "integer";
    const         FLOAT             = "float";
    private const DOUBLE            = "double";
    const         BOOL              = "bool";
    private const BOOL_LONG_NAME    = "boolean";
    private const BOOL_FALSE_NAME   = "false";
    const         STRING            = "string";

//    compound types
    const ARRAY                     = "array";
    const         ITERABLE          = "iterable";
    const CALLABLE                  = "callable";
    const         OBJECT            = "object";

//    resource
    const RESOURCE = "resource";

    const ANYTHING = "anything";
    const VOID     = "void";

    const MIXED = "mixed";
    const NULL  = "null";

    private string $type;

    private static function resolveCollectionTypes(string $foundCollectionTypes): array
    {
        $collectionTypes = explode(",", $foundCollectionTypes);
        $collectionTypes = array_map(
            function (string $type) {
                return self::removeSlashPrefix($type);
            }, $collectionTypes
        );

        return array_filter(
            $collectionTypes, function (string $type) {
            return $type !== self::MIXED;
        }
        );
    }

    /**
     * @return bool
     */
    public function isCompoundType(): bool
    {
        return in_array($this->type, [self::ARRAY, self::ITERABLE, self::CALLABLE, self::OBJECT]);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isItTypeOfScalar(string $type): bool
    {
        return in_array($type, [self::INTEGER, self::FLOAT, self::BOOL, self::STRING, self::INTEGER_LONG_NAME, self::DOUBLE, self::BOOL_LONG_NAME]);
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
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \ReflectionException
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

        if ($this->isAnything() || $toCompare->isAnything()) {
            return true;
        }

        if ($this->isScalar() && !$toCompare->isScalar()) {
            return false;
        }

        if (!$this->isScalar() && $toCompare->isScalar()) {
            if ($this->isClassOrInterface()) {
                if ($this->equals(TypeDescriptor::create(TypeDescriptor::OBJECT))) {
                    return false;
                }

                $toCompareClass = new \ReflectionClass($this->getTypeHint());

                if ($toCompare->isString() && $toCompareClass->hasMethod("__toString")) {
                    return true;
                }
            }

            return false;
        }

        if (($this->isClassOrInterface() && !$toCompare->isClassOrInterface()) || ($toCompare->isClassOrInterface() && !$this->isClassOrInterface())) {
            return false;
        }

        if ($this->isCollection() && $toCompare->isCollection()) {
            $thisGenericTypes     = $this->resolveGenericTypes();
            $comparedGenericTypes = $toCompare->resolveGenericTypes();

            if (count($thisGenericTypes) !== count($comparedGenericTypes)) {
                return false;
            }

            for ($index = 0; $index < count($thisGenericTypes); $index++) {
                if (!$thisGenericTypes[$index]->equals($comparedGenericTypes[$index])) {
                    return false;
                }
            }

            return true;
        }

        if ($this->isClassOrInterface() && $toCompare->isClassOrInterface()) {
            if (!$this->equals($toCompare)) {
                if (!$this->isCompoundObjectType() && $toCompare->isCompoundObjectType()) {
                    return true;
                }
                if ($this->isCompoundObjectType() && !$toCompare->isCompoundObjectType()) {
                    return false;
                }

                $thisClass      = new \ReflectionClass($this->getTypeHint());
                $toCompareClass = new \ReflectionClass($toCompare->getTypeHint());

                if ($thisClass->isInterface() && !$toCompareClass->isInterface()) {
                    return $toCompareClass->implementsInterface($this->getTypeHint());
                }
                if ($toCompareClass->isInterface() && !$thisClass->isInterface()) {
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

    private static function initialize(?string $typeHint, ?string $docBlockTypeDescription) : Type
    {
        $resolvedType = [];
        foreach (self::resolveType($typeHint)->getUnionTypes() as $declarationType) {
            if ($declarationType->isIterable() && !$declarationType->isCollection() && $docBlockTypeDescription) {
                try {
                    $docblockType = self::resolveType($docBlockTypeDescription);

                    if (!$docblockType->isCollection()) {
                        $resolvedType[] = $declarationType;
                        continue;
                    }

                    foreach ($docblockType->getUnionTypes() as $type) {
                        if ($type->isIterable()) {
                            $resolvedType[] = $type;
                        }
                    }
                }catch (TypeDefinitionException $exception) {
                    $resolvedType[] = $declarationType;
                }
            }else {
                $resolvedType[] = $declarationType;
            }
        }

        return UnionTypeDescriptor::createWith($resolvedType);
    }

    /**
     * @param string $typeHint
     *
     * @return bool
     * @throws \ReflectionException
     */
    public static function isInternalClassOrInterface(string $typeHint): bool
    {
        if (!self::isItTypeOfExistingClassOrInterface($typeHint)) {
            return false;
        }

        return (new \ReflectionClass($typeHint))->isInternal();
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
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @param $variable
     *
     * @return TypeDescriptor
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createFromVariable($variable): self
    {
        $type = strtolower(gettype($variable));

        if ($type === self::DOUBLE) {
            $type = self::FLOAT;
        } else if ($type === self::INTEGER_LONG_NAME) {
            $type = self::INTEGER;
        } else if ($type === self::BOOL_LONG_NAME) {
            $type = self::BOOL;
        } else if ($type === self::ARRAY) {
            if (empty($variable)) {
                return new self(self::ARRAY);
            }

            $collectionType = TypeDescriptor::createFromVariable(reset($variable));
            if (!$collectionType->isClassNotInterface()) {
                return new self(self::ARRAY);
            }

            foreach ($variable as $type) {
                if (!$collectionType->equals(TypeDescriptor::createFromVariable($type))) {
                    return new self(self::ARRAY);
                }
            }

            return self::createCollection($collectionType->toString());
        } else if ($type === self::ITERABLE) {
            $type = self::ITERABLE;
        } else if (is_callable($variable)) {
            $type = self::CALLABLE;
        } else if ($type === self::OBJECT) {
            $type = get_class($variable);
        } else if ($type === self::STRING) {
            $type = self::STRING;
        } else if ($type === self::RESOURCE) {
            $type = self::RESOURCE;
        } else if ($type === self::NULL) {
            $type = self::NULL;
        } else {
            $type = self::ANYTHING;
        }

        return new self($type);
    }

    /**
     * Should be called only, if type descriptor is collection
     *
     * @return TypeDescriptor[]
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function resolveGenericTypes(): array
    {
        if (!$this->isCollection()) {
            throw InvalidArgumentException::create("Can't resolve collection type on non collection");
        }

        preg_match(self::COLLECTION_TYPE_REGEX, $this->type, $match);

        return array_map(
            function (string $type) {
                return TypeDescriptor::create($type);
            }, self::resolveCollectionTypes($match[1])
        );
    }

    public static function createWithDocBlock(?string $type, ?string $docBlockTypeDescription): Type
    {
        return self::initialize($type, $docBlockTypeDescription);
    }

    /**
     * @param string $className
     *
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createCollection(string $className): \Ecotone\Messaging\Handler\TypeDescriptor
    {
        return new self("array<{$className}>");
    }

    /**
     * @param string $type
     *
     * @return Type
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function create(?string $type): Type
    {
        return self::initialize($type, "");
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createAnythingType(): \Ecotone\Messaging\Handler\TypeDescriptor
    {
        return new self(self::ANYTHING);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createBooleanType(): \Ecotone\Messaging\Handler\TypeDescriptor
    {
        return new self(self::BOOL);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createIntegerType(): \Ecotone\Messaging\Handler\TypeDescriptor
    {
        return new self(self::INTEGER);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createArrayType(): \Ecotone\Messaging\Handler\TypeDescriptor
    {
        return new self(self::ARRAY);
    }

    /**
     * Should be used instead of array, if array is not composed of scalar types or composition of types is unknown
     *
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createIterable(): \Ecotone\Messaging\Handler\TypeDescriptor
    {
        return new self(self::ITERABLE);
    }

    /**
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createStringType(): \Ecotone\Messaging\Handler\TypeDescriptor
    {
        return new self(self::STRING);
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
        return self::isItTypeOfExistingClassOrInterface($this->type) && ($this->type === $interfaceName || $this->type === "\\" . $interfaceName || is_subclass_of($this->type, $interfaceName));
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

    public function isSingleTypeCollection() : bool
    {
        return count($this->resolveGenericTypes()) === 1;
    }

    /**
     * @return bool
     */
    public function isNonCollectionArray() : bool
    {
        return $this->type === self::ARRAY;
    }

    /**
     * @return bool
     */
    public function isBoolean() : bool
    {
        return $this->type === self::BOOL;
    }

    /**
     * @return bool
     */
    public function isVoid() : bool
    {
        return $this->type === self::VOID;
    }

    /**
     * @return bool
     */
    public function isString() : bool
    {
        return $this->type === self::STRING;
    }

    /**
     * @return bool
     */
    public function isClassOrInterface() : bool
    {
        return $this->type === self::OBJECT || class_exists($this->type) || interface_exists($this->type);
    }

    public function isClassNotInterface() : bool
    {
        return class_exists($this->type);
    }

    /**
     * @return bool
     */
    public function isCompoundObjectType() : bool
    {
        return $this->type === self::OBJECT;
    }

    /**
     * @return bool
     */
    public function isPrimitive() : bool
    {
        return self::isItTypeOfPrimitive($this->type);
    }

    /**
     * @return bool
     */
    public function isAnything() : bool
    {
        return $this->type === self::ANYTHING;
    }

    /**
     * @param string $typeToCheck
     * @return bool
     */
    private static function isResolvableType(string $typeToCheck): bool
    {
        return self::isItTypeOfPrimitive($typeToCheck) || class_exists($typeToCheck) || interface_exists($typeToCheck) || $typeToCheck == self::ANYTHING || self::isItTypeOfCollection($typeToCheck);
    }

    /**
     * @return bool
     */
    public function isInterface() : bool
    {
        return interface_exists($this->type);
    }

    public function isAbstractClass() : bool
    {
        if (!$this->isClassOrInterface()) {
            return false;
        }

        if ($this->isCompoundObjectType()) {
            return false;
        }

        $reflectionClass = new \ReflectionClass($this->type);

        return $reflectionClass->isAbstract();
    }

    /**
     * @return bool
     */
    public function isScalar() : bool
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
     * @throws \Ecotone\Messaging\MessagingException
     */
    private static function resolveType(?string $typeHint) : Type
    {
        if (is_null($typeHint)) {
            return new self(self::ANYTHING);
        }
        $typeHint = trim($typeHint);
        if ($typeHint == "") {
            return new self(self::ANYTHING);
        }

        $finalTypes = [];
        foreach (explode("|", $typeHint) as $type) {
            if ($type === self::VOID) {
                $finalTypes[] = new self(self::VOID);
                continue;
            }

            if ($typeHint === self::INTEGER_LONG_NAME) {
                $finalTypes[] = new self(self::INTEGER);
                continue;
            }
            if ($typeHint === self::DOUBLE) {
                $finalTypes[] = new self(self::FLOAT);
                continue;
            }
            if (in_array($typeHint, [self::BOOL_LONG_NAME, self::BOOL_FALSE_NAME])) {
                $finalTypes[] = new self(self::BOOL);
                continue;
            }

            if (strpos($type, "[]") !== false) {
                $type = "array<" . str_replace("[]", "", $type) . ">";
            }

            if ($type === self::NULL) {
                $finalTypes[] = new self(self::NULL);
                continue;
            }
            if ($type === self::MIXED) {
                $finalTypes[] = new self(self::ANYTHING);
                continue;
            }
            if (!self::isResolvableType($type)) {
                throw TypeDefinitionException::create("Passed type hint `{$type}` is not resolvable");
            }

            if (preg_match(self::COLLECTION_TYPE_REGEX, $type, $match)) {
                $foundCollectionTypes = $match[1];
                $collectionTypes = self::resolveCollectionTypes($foundCollectionTypes);

                if (empty($collectionTypes)) {
                    $type = self::ARRAY;
                }else {
                    foreach ($collectionTypes as $collectionType) {
                        if (!self::isResolvableType($collectionType)) {
                            throw TypeDefinitionException::create("Unknown collection type in {$type}. Passed type in collection is not resolvable: {$collectionType}.");
                        }
                    }

                    $type = str_replace($foundCollectionTypes, implode(",", $collectionTypes), $match[0]);
                }
            }

            $finalTypes[] = new self(self::removeSlashPrefix($type));
        }

        return UnionTypeDescriptor::createWith($finalTypes);
    }

    /**
     * @return string
     */
    public function toString() : string
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
    public static function isItTypeOfCompoundType(string $type) : bool
    {
        return in_array($type, [self::ARRAY, self::ITERABLE, self::CALLABLE, self::OBJECT]);
    }

    /**
     * @inheritDoc
     */
    public function isUnionType(): bool
    {
        return false;
    }

    /**
     * @param string $type
     * @return string
     */
    private static function removeSlashPrefix(string $type): string
    {
        if (class_exists($type) || interface_exists($type)) {
            $type = ltrim($type, "\\");
        }

        return trim($type);
    }
}