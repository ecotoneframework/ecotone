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

    /**
     * @var string
     */
    private $name;
    /**
     * @var TypeDescriptor
     */
    private $typeDescriptor;
    /**
     * @var bool
     */
    private $doesAllowNull;

    /**
     * TypeHint constructor.
     * @param string $name
     * @param TypeDescriptor $typeDescriptor
     * @param bool $doesAllowNull
     */
    private function __construct(string $name, TypeDescriptor $typeDescriptor, bool $doesAllowNull)
    {
        $this->name = $name;
        $this->typeDescriptor = $typeDescriptor;
        $this->doesAllowNull = $doesAllowNull;
    }

    /**
     * @param string $name
     * @param TypeDescriptor $typeDescriptor
     * @return self
     */
    public static function createNullable(string $name, TypeDescriptor $typeDescriptor) : self
    {
        return new self($name, $typeDescriptor, true);
    }

    /**
     * @param string $name
     * @param TypeDescriptor $typeDescriptor
     * @return self
     */
    public static function createNotNullable(string $name, TypeDescriptor $typeDescriptor) : self
    {
        return new self($name, $typeDescriptor, false);
    }

    /**
     * @param string $name
     * @param TypeDescriptor $typeDescriptor
     * @param bool $doesAllowNull
     * @return self
     */
    public static function create(string $name, TypeDescriptor $typeDescriptor, bool $doesAllowNull) : self
    {
        return new self($name, $typeDescriptor, $doesAllowNull);
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
     * @return TypeDescriptor
     */
    public function getTypeDescriptor() : TypeDescriptor
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