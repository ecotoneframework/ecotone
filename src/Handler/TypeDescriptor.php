<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class TypeDescriptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class TypeDescriptor
{
    const COLLECTION_TYPE_REGEX = "/[a-zA-Z0-9]*<([^<]*)>/";

    //    scalar types
    const INTEGER = "int";
    const FLOAT = "float";
    const BOOL = "bool";
    const STRING = "string";

//    compound types
    const ARRAY = "array";
    const ITERABLE = "iterable";
    const CALLABLE = "callable";
    const OBJECT = "object";

//    resource
    const RESOURCE = "resource";

    const UNKNOWN = "unknown";
    const VOID = "void";

    private const MIXED = "mixed";

    /**
     * @var string
     */
    private $type;

    /**
     * @param string $type
     * @return bool
     */
    public static function isItTypeOfCompoundType(string $type) : bool
    {
        return in_array($type, [self::ARRAY, self::ITERABLE, self::CALLABLE, self::OBJECT]);
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isItTypeOfScalar(string $type) : bool
    {
        return in_array($type, [self::INTEGER, self::FLOAT, self::BOOL, self::STRING]);
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isItTypeOfPrimitive(string $type) : bool
    {
        return self::isItTypeOfCompoundType($type) || self::isItTypeOfScalar($type) || $type === self::RESOURCE;
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isItTypeOfCollection(string $type) : bool
    {
        return (bool)preg_match(self::COLLECTION_TYPE_REGEX, $type);
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isItTypeOfResource(string $type) : bool
    {
        return $type == self::RESOURCE;
    }

    /**
     * @param string $typeToCompare
     * @return bool
     */
    public static function isItTypeOfVoid(string $typeToCompare) : bool
    {
        return $typeToCompare === self::VOID;
    }

    /**
     * @param string $typeHint
     * @return bool
     */
    public static function isItTypeOfExistingClassOrInterface(string $typeHint) : bool
    {
        return class_exists($typeHint) || interface_exists($typeHint);
    }

    /**
     * @param TypeDescriptor $typeDescriptor
     * @return bool
     */
    public function sameTypeAs(TypeDescriptor $typeDescriptor) : bool
    {
        return $this->toString() === $typeDescriptor->toString();
    }

    /**
     * TypeHint constructor.
     * @param string $type
     * @param string $docBlockTypeDescription
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function __construct(string $type, string $docBlockTypeDescription)
    {
        $this->initialize($type, $docBlockTypeDescription);
    }

    /**
     * @param $variable
     * @return TypeDescriptor
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createFromVariable($variable) : self
    {
        $type = gettype($variable);

        if ($type === "double") {
            $type = self::FLOAT;
        }else if ($type === "integer") {
            $type = self::INTEGER;
        }else if ($type === self::ARRAY) {
            $type = self::ITERABLE;
        }else if (is_callable($variable)){
            $type = self::CALLABLE;
        }else if ($type === self::OBJECT) {
            $type = get_class($variable);
        }else if ($type === self::STRING) {
            $type = self::STRING;
        }else if ($type === self::RESOURCE){
            $type = self::RESOURCE;
        }else {
            $type = self::UNKNOWN;
        }

        return new self($type,  "");
    }

    /**
     * Should be called only, if type descriptor is collection
     *
     * @return TypeDescriptor[]
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function resolveGenericTypes() : array
    {
        if (!$this->isCollection()) {
            throw InvalidArgumentException::create("Can't resolve collection type on non collection");
        }

        preg_match(self::COLLECTION_TYPE_REGEX, $this->type, $match);

        return [TypeDescriptor::create(trim($match[1]))];
    }

    /**
     * @param string $type
     * @param string|null $docBlockTypeDescription
     * @return self
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createWithDocBlock(?string $type, ?string $docBlockTypeDescription) : self
    {
        return new self($type ? $type : self::UNKNOWN, $docBlockTypeDescription ? $docBlockTypeDescription : "");
    }

    /**
     * @param string $className
     * @return TypeDescriptor
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createCollection(string $className) : self
    {
        return new self("array<{$className}>", "");
    }

    /**
     * @param string $type
     * @return TypeDescriptor
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function create(?string $type) : self
    {
        return new self($type ? $type : self::UNKNOWN,"");
    }

    /**
     * @return TypeDescriptor
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createUnknown() : self
    {
        return new self(self::UNKNOWN, "");
    }

    /**
     * @return TypeDescriptor
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createArray() : self
    {
        return new self(self::ARRAY, "");
    }

    /**
     * @return TypeDescriptor
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createString() : self
    {
        return new self(self::STRING, "");
    }

    /**
     * @return string
     */
    public function getTypeHint() : string
    {
        return $this->type;
    }

    /**
     * @param TypeDescriptor $typeDescriptor
     * @return bool
     */
    public function equals(TypeDescriptor $typeDescriptor) : bool
    {
        return $this->getTypeHint() === $typeDescriptor->getTypeHint();
    }

    /**
     * @param string $interfaceName
     * @return bool
     */
    public function isClassOfType(string $interfaceName) : bool
    {
        return self::isItTypeOfExistingClassOrInterface($this->type) && ($this->type === $interfaceName || $this->type === "\\" . $interfaceName || is_subclass_of($this->type, $interfaceName));
    }

    /**
     * @return bool
     */
    public function isIterable() : bool
    {
        return $this->type === self::ARRAY || $this->type === self::ITERABLE || $this->isCollection();
    }

    /**
     * @return bool
     */
    public function isCollection(): bool
    {
        return self::isItTypeOfCollection($this->type);
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
    public function isObject() : bool
    {
        return $this->type === self::OBJECT || $this->isClassOrInterface($this->type);
    }

    /**
     * @return bool
     */
    public function isUnknown() : bool
    {
        return $this->type === self::UNKNOWN;
    }

    /**
     * @param string $typeToCheck
     * @return bool
     */
    private function isResolvableType(string $typeToCheck): bool
    {
        return self::isItTypeOfPrimitive($typeToCheck) || class_exists($typeToCheck) || interface_exists($typeToCheck) || $typeToCheck == self::UNKNOWN || self::isItTypeOfCollection($typeToCheck);
    }

    /**
     * @param string $typeHint
     * @return bool
     */
    private function isCompoundArray(string $typeHint) : bool
    {
        return in_array($typeHint, [self::ARRAY, self::ITERABLE]);
    }

    /**
     * @param string $typeHint
     * @return bool
     */
    private function isClassOrInterface(string $typeHint) : bool
    {
        return class_exists($typeHint) || interface_exists($typeHint);
    }

    /**
     * @param string $typeHint
     * @return bool
     */
    private function isUnknownType(string $typeHint) : bool
    {
        return $typeHint === self::UNKNOWN;
    }

    /**
     * @param string $typeHint
     * @param string $docBlockTypeDescription
     * @return bool
     */
    private function isScalarAndCompound(string $typeHint, string $docBlockTypeDescription): bool
    {
        return self::isItTypeOfScalar($typeHint) && self::isItTypeOfCompoundType($docBlockTypeDescription);
    }

    /**
     * @param string $typeHint
     * @param string $docBlockTypeDescription
     * @return bool
     */
    private function isCompoundAndScalar(string $typeHint, string $docBlockTypeDescription): bool
    {
        return self::isItTypeOfCompoundType($typeHint) && self::isItTypeOfScalar($docBlockTypeDescription);
    }

    /**
     * @param string $typeHint
     * @param string $docBlockTypeDescription
     * @return bool
     */
    private function isResourceAndScalar(string $typeHint, string $docBlockTypeDescription): bool
    {
        return self::isItTypeOfResource($typeHint) && self::isItTypeOfScalar($docBlockTypeDescription);
    }

    /**
     * @param string $typeHint
     * @param string $docBlockTypeDescription
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function initialize(string $typeHint, string $docBlockTypeDescription) : void
    {
        $typeHint = trim($typeHint);
        $docBlockTypeDescription = trim($docBlockTypeDescription);

        if ($typeHint === self::VOID) {
            $this->type = self::VOID;
            return;
        }

        if (
            $this->isScalarAndCompound($typeHint, $docBlockTypeDescription)
            || $this->isCompoundAndScalar($typeHint, $docBlockTypeDescription)
            || $this->isResourceAndScalar($typeHint, $docBlockTypeDescription)
            || $this->isScalarAndResource($typeHint, $docBlockTypeDescription)
            || $this->isResourceAndCompound($typeHint, $docBlockTypeDescription)
            || $this->isCompoundAndResource($typeHint, $docBlockTypeDescription)
            || $this->isCompoundAndClass($typeHint, $docBlockTypeDescription)
        ) {
            throw TypeDefinitionException::create("Passed type hint {$typeHint} is not compatible with doc block type {$docBlockTypeDescription}");
        }

        $type = $this->resolveType($typeHint);
        $docBlockType = $docBlockTypeDescription ? $this->resolveType($docBlockTypeDescription) : self::UNKNOWN;

        if (self::isItTypeOfCollection($docBlockType) && (!($this->isUnknownType($type) || $this->isCompoundArray($type)))) {
            throw TypeDefinitionException::create("Passed type hint {$typeHint} is not compatible with doc block type {$type}");
        }

        $this->type = $docBlockType !== self::UNKNOWN ? $docBlockType : $type;
    }

    /**
     * @param string $typeHint
     * @return string
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function resolveType(string $typeHint) : string
    {
        $type = explode("|", $typeHint)[0];

        if (strpos($type, "[]") !== false) {
            $type = "array<" . str_replace("[]", "", $type) . ">";
        }
        if ($typeHint === self::MIXED) {
            return self::UNKNOWN;
        }
        if (!$this->isResolvableType($type)) {
            throw TypeDefinitionException::create("Passed type hint is not resolvable: {$type}");
        }

        if (preg_match(self::COLLECTION_TYPE_REGEX, $type, $match)) {
            $collectionType = $this->addSlashPrefix($match[1]);
            if (!$this->isResolvableType($collectionType)) {
                throw TypeDefinitionException::create("Unknown type for {$typeHint}. Passed type hint is not resolvable: {$collectionType}.");
            }

            $type = str_replace($match[1], $collectionType, $type);
        }

        return $this->addSlashPrefix($type);
    }

    /**
     * @param string $typeHint
     * @param string $docBlockTypeDescription
     * @return bool
     */
    private function isScalarAndResource(string $typeHint, string $docBlockTypeDescription): bool
    {
        return self::isItTypeOfScalar($typeHint) && self::isItTypeOfResource($docBlockTypeDescription);
    }

    /**
     * @param string $typeHint
     * @param string $docBlockTypeDescription
     * @return bool
     */
    private function isResourceAndCompound(string $typeHint, string $docBlockTypeDescription): bool
    {
        return self::isItTypeOfResource($typeHint) && self::isItTypeOfCompoundType($docBlockTypeDescription);
    }

    /**
     * @param string $typeHint
     * @param string $docBlockTypeDescription
     * @return bool
     */
    private function isCompoundAndResource(string $typeHint, string $docBlockTypeDescription): bool
    {
        return self::isItTypeOfCompoundType($typeHint) && self::isItTypeOfResource($docBlockTypeDescription);
    }

    /**
     * @param string $typeHint
     * @param string $docBlockTypeDescription
     * @return bool
     */
    private function isCompoundAndClass(string $typeHint, string $docBlockTypeDescription): bool
    {
        return self::isItTypeOfCompoundType($typeHint) && self::isClassOrInterface($docBlockTypeDescription) && !$this->isCompoundClass($typeHint);
    }

    /**
     * @param string $typeHint
     * @return bool
     */
    private function isCompoundClass(string $typeHint): bool
    {
        return $typeHint === self::OBJECT;
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
     * @return string
     */
    private function addSlashPrefix(string $type): string
    {
        if ($this->isClassOrInterface($type)) {
            $type = substr($type, 0, 1) !== "\\" ? ("\\" . $type) : $type;
        }

        return trim($type);
    }
}