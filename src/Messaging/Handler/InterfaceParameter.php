<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\ErrorMessage;

/**
 * Class InterfaceParameter
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class InterfaceParameter
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var Type
     */
    private $typeDescriptor;
    /**
     * @var bool
     */
    private $doesAllowNull;
    /**
     * @var mixed
     */
    private $defaultValue;
    /**
     * @var bool
     */
    private $hasDefaultValue;
    /**
     * @var bool
     */
    private $isAnnotation;

    private function __construct(string $name, Type $typeDescriptor, bool $doesAllowNull, bool $hasDefaultValue, $defaultValue, bool $isAnnotation)
    {
        $this->name = $name;
        $this->typeDescriptor = $typeDescriptor;
        $this->doesAllowNull = $doesAllowNull;
        $this->hasDefaultValue = $hasDefaultValue;
        $this->defaultValue = $defaultValue;
        $this->isAnnotation = $isAnnotation;
    }

    /**
     * @param string $name
     * @param Type $typeDescriptor
     * @return self
     */
    public static function createNullable(string $name, Type $typeDescriptor) : self
    {
        return new self($name, $typeDescriptor, true, false,null, false);
    }

    /**
     * @param string $name
     * @param Type $typeDescriptor
     * @return self
     */
    public static function createNotNullable(string $name, Type $typeDescriptor) : self
    {
        return new self($name, $typeDescriptor, false, false, null, false);
    }

    public static function create(string $name, Type $typeDescriptor, bool $doesAllowNull, bool $hasDefaultValue, $defaultValue, bool $isAnnotation) : self
    {
        return new self($name, $typeDescriptor, $doesAllowNull, $hasDefaultValue, $defaultValue, $isAnnotation);
    }

    /**
     * @param Type $toCompare
     * @return bool
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function canBePassedIn(Type $toCompare) : bool
    {
        return $toCompare->isCompatibleWith($this->typeDescriptor);
    }

    /**
     * @param InterfaceParameter $interfaceParameter
     * @return bool
     */
    public function hasEqualTypeAs(InterfaceParameter $interfaceParameter) : bool
    {
        return $this->typeDescriptor->equals($interfaceParameter->typeDescriptor);
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
        return $this->doesAllowNull;
    }

    /**
     * @return string
     */
    public function getTypeHint() : string
    {
        return $this->typeDescriptor->getTypeHint();
    }

    /**
     * @return mixed|null
     */
    public function getDefaultValue()
    {
        Assert::isTrue($this->hasDefaultValue(), "Cannot retrieve default value, as it does not exists {$this}");

        return $this->defaultValue;
    }

    public function hasDefaultValue() : bool
    {
        return $this->hasDefaultValue;
    }

    /**
     * @return Type
     */
    public function getTypeDescriptor() : Type
    {
        return $this->typeDescriptor;
    }

    /**
     * @return bool
     */
    public function isMessage() : bool
    {
        return $this->typeDescriptor->equals(TypeDescriptor::create(Message::class)) || $this->typeDescriptor->equals(TypeDescriptor::create(ErrorMessage::class));
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
    public function isObjectTypeHint() : bool
    {
        return class_exists($this->getTypeHint());
    }

    public function __toString()
    {
        return $this->name . " " . $this->typeDescriptor->toString();
    }
}