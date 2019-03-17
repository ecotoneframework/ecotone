<?php

namespace SimplyCodedSoftware\Messaging\Handler;

/**
 * Class PropertyDescriptor
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class ClassPropertyDefinition
{
    private const PUBLIC_VISIBILITY = "public";
    private const PROTECTED_VISIBILITY = "protected";
    private const PRIVATE_VISIBILITY = "private";

    /**
     * @var string
     */
    private $name;
    /**
     * @var TypeDescriptor
     */
    private $type;
    /**
     * @var bool
     */
    private $isNullable;
    /**
     * @var bool
     */
    private $isStatic;
    /**
     * @var string
     */
    private $visibility;
    /**
     * @var object[]
     */
    private $annotations;

    /**
     * PropertyDescriptor constructor.
     * @param string $name
     * @param TypeDescriptor $type
     * @param bool $isNullable
     * @param string $visibility
     * @param bool $isStatic
     * @param object[] $annotations
     */
    private function __construct(string $name, TypeDescriptor $type, bool $isNullable, string $visibility, bool $isStatic, iterable $annotations)
    {
        $this->name = $name;
        $this->type = $type;
        $this->isNullable = $isNullable;
        $this->visibility = $visibility;
        $this->isStatic = $isStatic;
        $this->annotations = $annotations;
    }

    /**
     * @param string $name
     * @param TypeDescriptor $typeDescriptor
     * @param bool $isNullable
     * @param bool $isStatic
     * @param iterable $annotations
     * @return ClassPropertyDefinition
     */
    public static function createPublic(string $name, TypeDescriptor $typeDescriptor, bool $isNullable, bool $isStatic, iterable $annotations) : self
    {
        return new self($name, $typeDescriptor, $isNullable, self::PUBLIC_VISIBILITY, $isStatic, $annotations);
    }

    /**
     * @param string $name
     * @param TypeDescriptor $typeDescriptor
     * @param bool $isNullable
     * @param bool $isStatic
     * @param iterable $annotations
     * @return ClassPropertyDefinition
     */
    public static function createProtected(string $name, TypeDescriptor $typeDescriptor, bool $isNullable, bool $isStatic, iterable $annotations) : self
    {
        return new self($name, $typeDescriptor, $isNullable, self::PROTECTED_VISIBILITY, $isStatic, $annotations);
    }

    /**
     * @param string $name
     * @param TypeDescriptor $typeDescriptor
     * @param bool $isNullable
     * @param bool $isStatic
     * @param iterable $annotations
     * @return ClassPropertyDefinition
     */
    public static function createPrivate(string $name, TypeDescriptor $typeDescriptor, bool $isNullable, bool $isStatic, iterable $annotations) : self
    {
        return new self($name, $typeDescriptor, $isNullable, self::PRIVATE_VISIBILITY, $isStatic, $annotations);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasName(string $name) : bool
    {
        return $this->name == $name;
    }

    /**
     * @return bool
     */
    public function isPrivate() : bool
    {
        return $this->visibility === self::PRIVATE_VISIBILITY;
    }

    /**
     * @return bool
     */
    public function isPublic() : bool
    {
        return $this->visibility === self::PUBLIC_VISIBILITY;
    }

    /**
     * @return bool
     */
    public function isProtected() : bool
    {
        return $this->visibility === self::PROTECTED_VISIBILITY;
    }
}