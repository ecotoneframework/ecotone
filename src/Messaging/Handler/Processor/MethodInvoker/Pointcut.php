<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * Class Pointcut
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
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
     * @param object[] $endpointAnnotations
     * @return bool
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function doesItCut(InterfaceToCall $interfaceToCall, iterable $endpointAnnotations) : bool
    {
        if (is_null($this->expression)) {
            return false;
        }

        $multipleExpression = explode("||", $this->expression);

        foreach ($multipleExpression as $expression) {
            if ($this->isRelatedClass($expression, $interfaceToCall)) {
                return true;
            }
            if (strpos($expression, "::") !== false) {
                list($class, $method) = explode("::", $expression);

                if ($this->isRelatedClass($class, $interfaceToCall)) {
                    $method = str_replace("()", "", $method);
                    if ($interfaceToCall->hasMethodName($method)) {
                        return true;
                    }
                }
            }

            if (strpos($expression, "@(") !== false) {
                $annotationToCheck = str_replace(["@(", ")"], "", $expression);
                $annotationToCheck = TypeDescriptor::create($annotationToCheck);

                foreach ($endpointAnnotations as $endpointAnnotation) {
                    $endpointType = TypeDescriptor::createFromVariable($endpointAnnotation);

                    if ($endpointType->equals($annotationToCheck)) {
                        return true;
                    }
                }

                if ($interfaceToCall->hasMethodAnnotation($annotationToCheck)
                    || $interfaceToCall->hasClassAnnotation($annotationToCheck)) {
                    return true;
                }
            }else if (strpos($expression, "*") !== false) {
                $expression = "#" . str_replace("*", ".*", $expression) . "#";
                $expression = str_replace("\\", "\\\\", $expression);

                return preg_match($expression, $interfaceToCall->getInterfaceName()) === 1;
            }
        }

        return false;
    }

    /**
     * @param string $expression
     * @param InterfaceToCall $interfaceToCall
     * @return bool
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function isRelatedClass(string $expression, InterfaceToCall $interfaceToCall) : bool
    {
        if (TypeDescriptor::isItTypeOfExistingClassOrInterface($expression)) {
            return $interfaceToCall->getInterfaceType()->equals(TypeDescriptor::create($expression));
        }

        return false;
    }
}