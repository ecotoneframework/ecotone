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
    private $docCommentTypes;
    /**
     * @var bool
     */
    private $doesAllowNulls;

    /**
     * TypeHint constructor.
     * @param string $name
     * @param string $type
     * @param bool $doesAllowNulls
     * @param string[] $docCommentTypes
     */
    private function __construct(string $name, string $type, bool $doesAllowNulls, array $docCommentTypes)
    {
        $this->name = $name;
        $this->type = $type;
        $this->docCommentTypes = $docCommentTypes;
        $this->doesAllowNulls = $doesAllowNulls;
    }

    /**
     * @param string $name
     * @param string $type
     * @param bool $doesAllowNulls
     * @param array $docCommentType
     * @return self
     */
    public static function create(string $name, string $type, bool $doesAllowNulls, array $docCommentType) : self
    {
        return new self($name, $type, $doesAllowNulls, $docCommentType);
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
     * @return bool
     */
    public function isObjectTypeHint() : bool
    {
        return class_exists($this->getTypeHint());
    }
}