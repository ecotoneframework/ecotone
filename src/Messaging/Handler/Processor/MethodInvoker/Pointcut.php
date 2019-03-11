<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;

/**
 * Class Pointcut
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class Pointcut
{
    /**
     * @var string|null
     */
    private $expression;

    /**
     * Pointcut constructor.
     * @param string|null $expression
     */
    private function __construct(?string $expression)
    {
        $this->expression = $expression;
    }

    /**
     * @param string $expression
     * @return Pointcut
     */
    public static function createWith(string $expression) : self
    {
        return new self($expression);
    }

    /**
     * @return Pointcut
     */
    public static function createEmpty() : self
    {
        return new self(null);
    }

    /**
     * @param InterfaceToCall $interfaceToCall
     * @return bool
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function doesItCut(InterfaceToCall $interfaceToCall) : bool
    {
        if (is_null($this->expression)) {
            return false;
        }

        if ($this->isRelatedClass($this->expression, $interfaceToCall)) {
            return true;
        }
        if (strpos($this->expression, "::") !== false) {
            list($class, $method) = explode("::", $this->expression);

            if ($this->isRelatedClass($class, $interfaceToCall)) {
                $method = str_replace("()", "", $method);
                if ($interfaceToCall->hasMethodName($method)) {
                    return true;
                }
            }
        }

        if (strpos($this->expression, "@method(") !== false) {
            $methodAnnotation = str_replace(["@method(", ")"], "", $this->expression);

            return $interfaceToCall->hasMethodAnnotation(TypeDescriptor::create($methodAnnotation));
        }

        if (strpos($this->expression, "@class(") !== false) {
            $classAnnotation = str_replace(["@class(", ")"], "", $this->expression);

            return $interfaceToCall->hasClassAnnotation(TypeDescriptor::create($classAnnotation));
        }

        return false;
    }

    /**
     * @param string $expression
     * @param InterfaceToCall $interfaceToCall
     * @return bool
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function isRelatedClass(string $expression, InterfaceToCall $interfaceToCall) : bool
    {
        if (TypeDescriptor::isItTypeOfExistingClassOrInterface($expression)) {
            return $interfaceToCall->getInterfaceType()->equals(TypeDescriptor::create($expression));
        }

        return false;
    }
}