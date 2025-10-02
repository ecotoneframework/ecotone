<?php

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class PropertyDescriptor
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
final class ClassPropertyDefinition
{
    private const PUBLIC_VISIBILITY = 'public';
    private const PROTECTED_VISIBILITY = 'protected';
    private const PRIVATE_VISIBILITY = 'private';

    private string $name;
    private Type $type;
    private bool $isNullable;
    private bool $isStatic;
    private string $visibility;
    /**
     * @var object[]
     */
    private iterable $annotations;

    /**
     * PropertyDescriptor constructor.
     * @param string $name
     * @param Type $type
     * @param bool $isNullable
     * @param string $visibility
     * @param bool $isStatic
     * @param object[] $annotations
     */
    private function __construct(string $name, Type $type, bool $isNullable, string $visibility, bool $isStatic, iterable $annotations)
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
     * @param Type $typeDescriptor
     * @param bool $isNullable
     * @param bool $isStatic
     * @param iterable $annotations
     * @return ClassPropertyDefinition
     */
    public static function createPublic(string $name, Type $typeDescriptor, bool $isNullable, bool $isStatic, iterable $annotations): self
    {
        return new self($name, $typeDescriptor, $isNullable, self::PUBLIC_VISIBILITY, $isStatic, $annotations);
    }

    /**
     * @param string $name
     * @param Type $typeDescriptor
     * @param bool $isNullable
     * @param bool $isStatic
     * @param iterable $annotations
     * @return ClassPropertyDefinition
     */
    public static function createProtected(string $name, Type $typeDescriptor, bool $isNullable, bool $isStatic, iterable $annotations): self
    {
        return new self($name, $typeDescriptor, $isNullable, self::PROTECTED_VISIBILITY, $isStatic, $annotations);
    }

    /**
     * @param string $name
     * @param Type $typeDescriptor
     * @param bool $isNullable
     * @param bool $isStatic
     * @param iterable $annotations
     * @return ClassPropertyDefinition
     */
    public static function createPrivate(string $name, Type $typeDescriptor, bool $isNullable, bool $isStatic, iterable $annotations): self
    {
        return new self($name, $typeDescriptor, $isNullable, self::PRIVATE_VISIBILITY, $isStatic, $annotations);
    }


    public function hasAnnotation(Type $annotationClass): bool
    {
        foreach ($this->annotations as $annotation) {
            if ($annotationClass->accepts($annotation)) {
                return true;
            }
        }

        return false;
    }

    public function getAnnotation(Type $annotationClass): object
    {
        foreach ($this->annotations as $annotation) {
            if ($annotationClass->accepts($annotation)) {
                return $annotation;
            }
        }

        throw InvalidArgumentException::create("Annotation {$annotationClass} was not found for property {$this}");
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Type
     */
    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasName(string $name): bool
    {
        return $this->name == $name;
    }

    /**
     * @return object[]
     */
    public function getAnnotations(): iterable
    {
        return $this->annotations;
    }

    /**
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    /**
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    /**
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->visibility === self::PRIVATE_VISIBILITY;
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->visibility === self::PUBLIC_VISIBILITY;
    }

    /**
     * @return bool
     */
    public function isProtected(): bool
    {
        return $this->visibility === self::PROTECTED_VISIBILITY;
    }

    public function __toString()
    {
        return '`' . $this->visibility . ':' . $this->name . '` (' . $this->type->toString() . ')';
    }
}
