<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Message;

/**
 * Class InterfaceParameter
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class InterfaceParameter
{
    /** http://php.net/manual/en/language.types.intro.php */

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
     * TypeHint constructor.
     * @param string $name
     * @param Type $typeDescriptor
     * @param bool $doesAllowNull
     */
    private function __construct(string $name, Type $typeDescriptor, bool $doesAllowNull)
    {
        $this->name = $name;
        $this->typeDescriptor = $typeDescriptor;
        $this->doesAllowNull = $doesAllowNull;
    }

    /**
     * @param string $name
     * @param Type $typeDescriptor
     * @return self
     */
    public static function createNullable(string $name, Type $typeDescriptor) : self
    {
        return new self($name, $typeDescriptor, true);
    }

    /**
     * @param string $name
     * @param Type $typeDescriptor
     * @return self
     */
    public static function createNotNullable(string $name, Type $typeDescriptor) : self
    {
        return new self($name, $typeDescriptor, false);
    }

    /**
     * @param string $name
     * @param Type $typeDescriptor
     * @param bool $doesAllowNull
     * @return self
     */
    public static function create(string $name, Type $typeDescriptor, bool $doesAllowNull) : self
    {
        return new self($name, $typeDescriptor, $doesAllowNull);
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
        return $this->getTypeHint() === Message::class || $this->getTypeHint() === ("\\" . Message::class) || is_subclass_of($this->getTypeHint(), Message::class);
    }

    /**
     * @return bool
     */
    public function isObjectTypeHint() : bool
    {
        return class_exists($this->getTypeHint());
    }
}