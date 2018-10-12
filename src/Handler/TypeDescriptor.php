<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

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

    /**
     * @var string
     */
    private $type;
    /**
     * @var bool
     */
    private $doesAllowNulls;

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
     * TypeHint constructor.
     * @param string $type
     * @param bool $doesAllowNulls
     * @param string $docBlockTypeDescription
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function __construct(string $type, bool $doesAllowNulls, string $docBlockTypeDescription)
    {
        $this->initialize($type, $docBlockTypeDescription);
        $this->doesAllowNulls = $doesAllowNulls;
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

        return new self($type, true, "");
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

        if (!$this->isResolvableType($typeHint)) {
            throw TypeDefinitionException::create("Passed type hint is not resolvable: {$typeHint}");
        }

        $type = $typeHint;
        $docBlockTypeDescription = explode("|", $docBlockTypeDescription)[0];
        if (
            ($this->isCompoundClass($typeHint) && $this->isClassOrInterface($docBlockTypeDescription))
            || ($this->isClassOrInterface($typeHint) && $this->isClassOrInterface($docBlockTypeDescription))
        ) {
            $type = $docBlockTypeDescription;
        }
        if ($this->isUnknownType($typeHint) && $this->isResolvableType($docBlockTypeDescription)) {
            $type = $docBlockTypeDescription;
        }

        if (strpos($docBlockTypeDescription, "[]") !==  false) {
            $type = "array<" . str_replace("[]", "", $docBlockTypeDescription) . ">";
        }
        if (preg_match(self::COLLECTION_TYPE_REGEX, $docBlockTypeDescription, $match)) {
            if (!($this->isUnknownType($typeHint) || $this->isCompoundArray($typeHint))) {
                throw TypeDefinitionException::create("Passed type hint {$typeHint} is not compatible with doc block type {$docBlockTypeDescription}");
            }

            $collectionType = trim($match[1]);
            if (!$this->isResolvableType($collectionType)) {
                throw TypeDefinitionException::create("Unknown type for docblock {$docBlockTypeDescription}. Passed type hint is not resolvable: {$collectionType}.");
            }

            $type = $docBlockTypeDescription;
        }

        if ($this->isClassOrInterface($type)) {
            $type = substr($type, 0, 1) !== "\\" ? ("\\" . $type) : $type;
        }

        $this->type = $type;
    }

    /**
     * @param string $type
     * @param bool $doesAllowNulls
     * @param string|null $docBlockTypeDescription
     * @return self
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createWithDocBlock(?string $type, bool $doesAllowNulls, ?string $docBlockTypeDescription) : self
    {
        return new self($type ? $type : self::UNKNOWN, $doesAllowNulls, $docBlockTypeDescription ? $docBlockTypeDescription : "");
    }

    /**
     * @param string $className
     * @return TypeDescriptor
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createCollection(string $className) : self
    {
        return new self("array<$className>", true, "");
    }

    /**
     * @param string $type
     * @param bool $doesAllowNulls
     * @return TypeDescriptor
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function create(?string $type, bool $doesAllowNulls) : self
    {
        return new self($type ? $type : self::UNKNOWN, $doesAllowNulls, "");
    }

    /**
     * @return bool
     */
    public function doesAllowNulls() : bool
    {
        return $this->doesAllowNulls;
    }

    /**
     * @return string
     */
    public function getTypeHint() : string
    {
        return $this->type;
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
        return $this->type === self::ARRAY || $this->type === self::ITERABLE || self::isItTypeOfCollection($this->type);
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
    public function __toString()
    {
        return $this->type;
    }
}