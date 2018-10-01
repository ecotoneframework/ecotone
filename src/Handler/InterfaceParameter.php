<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class InterfaceParameter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class InterfaceParameter
{
    /** http://php.net/manual/en/language.types.intro.php */

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
    private $name;
    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $docBlockLeadingType;
    /**
     * @var bool
     */
    private $doesAllowNulls;

    /**
     * @param string $type
     * @return bool
     */
    public static function isCompoundType(string $type) : bool
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
        return self::isCompoundType($type) || self::isScalar($type) || $type === self::RESOURCE;
    }

    /**
     * TypeHint constructor.
     * @param string $name
     * @param string $type
     * @param bool $doesAllowNulls
     * @param string $docBlockLeadingType
     */
    private function __construct(string $name, string $type, bool $doesAllowNulls, string $docBlockLeadingType)
    {
        $this->name = $name;
        $this->type = $type;
        $this->docBlockLeadingType = $docBlockLeadingType;
        $this->doesAllowNulls = $doesAllowNulls;
    }

    /**
     * @param string $name
     * @param string $type
     * @param bool $doesAllowNulls
     * @param string $docBlockLeadingType
     * @return self
     */
    public static function create(string $name, string $type, bool $doesAllowNulls, string $docBlockLeadingType) : self
    {
        return new self($name, $type, $doesAllowNulls, $docBlockLeadingType);
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
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
     * @return bool
     */
    public function isMessage() : bool
    {
        return $this->getTypeHint() === Message::class || is_subclass_of($this->getTypeHint(), Message::class);
    }

    /**
     * @return string
     */
    public function getDocBlockLeadingType(): string
    {
        return $this->docBlockLeadingType;
    }

    /**
     * @return bool
     */
    public function isObjectTypeHint() : bool
    {
        return class_exists($this->getTypeHint());
    }
}