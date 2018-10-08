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
     * TypeHint constructor.
     * @param string $name
     * @param TypeDescriptor $typeDescriptor
     */
    private function __construct(string $name, TypeDescriptor $typeDescriptor)
    {
        $this->name = $name;
        $this->typeDescriptor = $typeDescriptor;
    }

    /**
     * @param string $name
     * @param TypeDescriptor $typeDescriptor
     * @return self
     */
    public static function create(string $name, TypeDescriptor $typeDescriptor) : self
    {
        return new self($name, $typeDescriptor);
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
        return $this->typeDescriptor->doesAllowNulls();
    }

    /**
     * @return string
     */
    public function getTypeHint() : string
    {
        return $this->typeDescriptor->getTypeHint();
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