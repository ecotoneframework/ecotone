<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Handler\Type\UnionType;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\ErrorMessage;
use ReflectionException;

/**
 * Class InterfaceParameter
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
final class InterfaceParameter
{
    private string $name;
    private Type $typeDescriptor;
    private bool $doesAllowNull;
    /**
     * @var mixed
     */
    private $defaultValue;
    private bool $hasDefaultValue;
    private bool $isAnnotation;
    /**
     * @var object[]
     */
    private array $annotations;

    public function __construct(string $name, Type $typeDescriptor, bool $doesAllowNull, bool $hasDefaultValue, $defaultValue, bool $isAnnotation, array $annotations)
    {
        $this->name = $name;
        $this->typeDescriptor = $typeDescriptor;
        $this->doesAllowNull = $doesAllowNull;
        $this->hasDefaultValue = $hasDefaultValue;
        $this->defaultValue = $defaultValue;
        $this->isAnnotation = $isAnnotation;
        $this->annotations = $annotations;
    }

    /**
     * @param string $name
     * @param Type $typeDescriptor
     * @return self
     */
    public static function createNullable(string $name, Type $typeDescriptor): self
    {
        return new self($name, $typeDescriptor, true, false, null, false, []);
    }

    /**
     * @param string $name
     * @param Type $typeDescriptor
     * @return self
     */
    public static function createNotNullable(string $name, Type $typeDescriptor): self
    {
        return new self($name, $typeDescriptor, false, false, null, false, []);
    }

    public static function create(string $name, Type $typeDescriptor, bool $doesAllowNull, bool $hasDefaultValue, $defaultValue, bool $isAnnotation, array $annotations): self
    {
        return new self($name, $typeDescriptor, $doesAllowNull, $hasDefaultValue, $defaultValue, $isAnnotation, $annotations);
    }

    /**
     * @param Type $toCompare
     * @return bool
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     * @throws ReflectionException
     */
    public function canBePassedIn(Type $toCompare): bool
    {
        return $toCompare->isCompatibleWith($this->typeDescriptor);
    }

    /**
     * @param InterfaceParameter $interfaceParameter
     * @return bool
     */
    public function hasEqualTypeAs(InterfaceParameter $interfaceParameter): bool
    {
        return $this->typeDescriptor->equals($interfaceParameter->typeDescriptor);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function doesAllowNulls(): bool
    {
        return $this->doesAllowNull;
    }

    public function getTypeHint(): string
    {
        if ($this->typeDescriptor instanceof UnionType) {
            return $this->typeDescriptor->withoutNull()->toString();
        }
        return $this->typeDescriptor->toString();
    }

    public function getAnnotations(): array
    {
        return $this->annotations;
    }

    public function hasAnnotation(string $type): bool
    {
        foreach ($this->annotations as $annotation) {
            if ($annotation instanceof $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return object[]
     */
    public function getAnnotationsOfType(string $type): array
    {
        $foundAnnotations = [];

        foreach ($this->annotations as $annotation) {
            if ($annotation instanceof $type) {
                $foundAnnotations[] = $annotation;
            }
        }

        return $foundAnnotations;
    }

    /**
     * @return mixed|null
     */
    public function getDefaultValue()
    {
        Assert::isTrue($this->hasDefaultValue(), "Cannot retrieve default value, as it does not exists {$this}");

        return $this->defaultValue;
    }

    public function hasDefaultValue(): bool
    {
        return $this->hasDefaultValue;
    }

    /**
     * @return Type
     */
    public function getTypeDescriptor(): Type
    {
        return $this->typeDescriptor;
    }

    /**
     * @return bool
     */
    public function isMessage(): bool
    {
        return $this->typeDescriptor->isIdentifiedBy(Message::class, ErrorMessage::class);
    }

    /**
     * @return bool
     */
    public function isAnnotation(): bool
    {
        return $this->isAnnotation;
    }

    /**
     * @return bool
     */
    public function isClassOrInterface(): bool
    {
        return class_exists($this->getTypeHint()) || interface_exists($this->getTypeHint());
    }

    public function __toString()
    {
        return $this->name;
    }
}
