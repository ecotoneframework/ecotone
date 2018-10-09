<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

/**
 * Class TypeDescriptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TypeDescriptor
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
    public static function isPrimitiveCompoundType(string $type) : bool
    {
        return in_array($type, [self::ARRAY, self::ITERABLE, self::CALLABLE, self::OBJECT]);
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isScalar(string $type) : bool
    {
        return in_array($type, [self::INTEGER, self::FLOAT, self::BOOL, self::STRING]);
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isPrimitiveType(string $type) : bool
    {
        return self::isPrimitiveCompoundType($type) || self::isScalar($type) || $type === self::RESOURCE;
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isCollection(string $type) : bool
    {
        return (bool)preg_match(self::COLLECTION_TYPE_REGEX, $type);
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isResource(string $type) : bool
    {
        return $type == self::RESOURCE;
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
     * @param string $typeHint
     * @param string $docBlockTypeDescription
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function initialize(string $typeHint, string $docBlockTypeDescription) : void
    {
        $typeHint = trim($typeHint);
        $docBlockTypeDescription = trim($docBlockTypeDescription);

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
        if ($this->isUnknown($typeHint) && $this->isResolvableType($docBlockTypeDescription)) {
            $type = $docBlockTypeDescription;
        }

        if (strpos($docBlockTypeDescription, "[]") !==  false) {
            $type = "array<" . str_replace("[]", "", $docBlockTypeDescription) . ">";
        }
        if (preg_match(self::COLLECTION_TYPE_REGEX, $docBlockTypeDescription, $match)) {
            if (!($this->isUnknown($typeHint) || $this->isCompoundArray($typeHint))) {
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
     * @param string $typeToCheck
     * @return bool
     */
    private function isResolvableType(string $typeToCheck): bool
    {
        return self::isPrimitiveType($typeToCheck) || class_exists($typeToCheck) || interface_exists($typeToCheck) || $typeToCheck == self::UNKNOWN || self::isCollection($typeToCheck);
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
    private function isUnknown(string $typeHint) : bool
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
        return self::isScalar($typeHint) && self::isPrimitiveCompoundType($docBlockTypeDescription);
    }

    /**
     * @param string $typeHint
     * @param string $docBlockTypeDescription
     * @return bool
     */
    private function isCompoundAndScalar(string $typeHint, string $docBlockTypeDescription): bool
    {
        return self::isPrimitiveCompoundType($typeHint) && self::isScalar($docBlockTypeDescription);
    }

    /**
     * @param string $typeHint
     * @param string $docBlockTypeDescription
     * @return bool
     */
    private function isResourceAndScalar(string $typeHint, string $docBlockTypeDescription): bool
    {
        return self::isResource($typeHint) && self::isScalar($docBlockTypeDescription);
    }

    /**
     * @param string $typeHint
     * @param string $docBlockTypeDescription
     * @return bool
     */
    private function isScalarAndResource(string $typeHint, string $docBlockTypeDescription): bool
    {
        return self::isScalar($typeHint) && self::isResource($docBlockTypeDescription);
    }

    /**
     * @param string $typeHint
     * @param string $docBlockTypeDescription
     * @return bool
     */
    private function isResourceAndCompound(string $typeHint, string $docBlockTypeDescription): bool
    {
        return self::isResource($typeHint) && self::isPrimitiveCompoundType($docBlockTypeDescription);
    }

    /**
     * @param string $typeHint
     * @param string $docBlockTypeDescription
     * @return bool
     */
    private function isCompoundAndResource(string $typeHint, string $docBlockTypeDescription): bool
    {
        return self::isPrimitiveCompoundType($typeHint) && self::isResource($docBlockTypeDescription);
    }

    /**
     * @param string $typeHint
     * @param string $docBlockTypeDescription
     * @return bool
     */
    private function isCompoundAndClass(string $typeHint, string $docBlockTypeDescription): bool
    {
        return self::isPrimitiveCompoundType($typeHint) && self::isClassOrInterface($docBlockTypeDescription) && !$this->isCompoundClass($typeHint);
    }

    /**
     * @param string $typeHint
     * @return bool
     */
    private function isCompoundClass(string $typeHint): bool
    {
        return $typeHint === self::OBJECT;
    }
}