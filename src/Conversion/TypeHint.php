<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Conversion;

/**
 * Class TypeHint
 * @package SimplyCodedSoftware\IntegrationMessaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TypeHint
{
    const INTEGER = "int";
    const FLOAT = "float";
    const BOOL = "bool";
    const STRING = "string";

    const ARRAY = "array";
    const ITERABLE = "iterable";
    const CALLABLE = "callable";
    const OBJECT = "object";

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
     * @var string[]
     */
    private $docCommentType;
    /**
     * @var bool
     */
    private $doesAllowNulls;

    /**
     * TypeHint constructor.
     * @param string $name
     * @param string $type
     * @param bool $doesAllowNulls
     * @param string[] $docCommentType
     */
    private function __construct(string $name, string $type, bool $doesAllowNulls, array $docCommentType)
    {
        $this->name = $name;
        $this->type = $type;
        $this->docCommentType = $docCommentType;
        $this->doesAllowNulls = $doesAllowNulls;
    }

    /**
     * @param string $name
     * @param string $type
     * @param bool $doesAllowNulls
     * @param array $docCommentType
     * @return TypeHint
     */
    public static function create(string $name, string $type, bool $doesAllowNulls, array $docCommentType) : self
    {
        return new self($name, $type, $doesAllowNulls, $docCommentType);
    }

    /**
     * @return bool returns true if is integer, float, bool, string
     */
    public function isScalar() : bool
    {
        return in_array($this->type, [self::INTEGER, self::FLOAT, self::BOOL, self::STRING]);
    }

    /**
     * @return bool returns true if is array, iterable, callable or object
     */
    public function isCompoundType() : bool
    {
        return in_array($this->type, [self::ARRAY, self::ITERABLE, self::CALLABLE, self::OBJECT]);
    }

    /**
     * @return bool
     */
    public function isUnknown() : bool
    {
        return $this->type === self::UNKNOWN;
    }

    /**
     * @return bool
     */
    public function isObject() : bool
    {
        return !$this->isScalar() && !$this->isUnknown() && !in_array($this->type, [self::ARRAY, self::ITERABLE, self::CALLABLE]);
    }

    /**
     * Returns if type hint targets some specific class/interface, instead of `object`
     *
     * @return bool
     */
    public function isDefinedObject() : bool
    {
        return $this->isObject() && $this->getType() !== self::OBJECT;
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isDoesAllowNulls(): bool
    {
        return $this->doesAllowNulls;
    }
}