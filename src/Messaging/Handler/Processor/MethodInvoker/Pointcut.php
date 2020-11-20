<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Handler\UnionTypeDescriptor;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class Pointcut
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class Pointcut
{
    private ?string $expression;

    private function __construct(?string $expression)
    {
        $results = $this->getExpressionsDivinedByBrackets($expression);

        if ($results) {
            Assert::isTrue(count($results) === count($this->getInBetweenBracketsExpressions($expression)) + 1, "Expression {$expression} is missing `||` or `&&` between brackets or `{}&&{}` bracket is missing on one of the expressions");
        }

        $this->expression = $expression;
    }

    /**
     * @param string $expression
     *
     * @return Pointcut
     */
    public static function createWith(string $expression): self
    {
        return new self($expression);
    }

    public static function initializeFrom(InterfaceToCall $interfaceToCall, array $parameterConverters) : self
    {
        $optionalAttributes = [];
        $requiredAttributes = [];
        foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
            if (self::hasConverter($parameterConverters, $interfaceParameter)) {
                continue;
            }

            /** @var UnionTypeDescriptor|TypeDescriptor $type */
            $type = $interfaceParameter->getTypeDescriptor();
            if ($type->isUnionType()) {
                if (!self::doesContainAnnotation($type)) {
                    continue;
                }

                foreach ($type->getUnionTypes() as $unionType) {
                    if ($interfaceParameter->doesAllowNulls()) {
                        throw InvalidArgumentException::create("Error during initialization of pointcut. Union types can only be non nullable for expressions in {$interfaceToCall} parameter: {$interfaceParameter}");
                    }
                    if (!$unionType->isClassOrInterface() || !ClassDefinition::createFor($unionType)->isAnnotation()) {
                        throw InvalidArgumentException::create("Error during initialization of pointcut. Union types can only combined from attributes, non attribute type given {$unionType->toString()} in {$interfaceToCall} parameter: {$interfaceParameter}");
                    }

                    $optionalAttributes[] = $unionType->toString();
                }
            }else {
                if (!$type->isClassNotInterface()) {
                    continue;
                }
                if (!ClassDefinition::createFor($type)->isAnnotation()) {
                    continue;
                }

                if ($interfaceParameter->doesAllowNulls()) {
                    $optionalAttributes[] = $type->toString();
                }else {
                    $requiredAttributes[] = $type->toString();
                }
            }
        }

        $pointcut = "";
        if ($optionalAttributes) {
            $pointcut = "(" .  implode("||", $optionalAttributes) . ")";
        }
        if ($requiredAttributes) {
            $pointcut .= $pointcut ? "&&" : "";
            $pointcut .= implode("&&", array_map(fn(string $attribute) => "(" . $attribute . ")", $requiredAttributes));
        }

        return Pointcut::createWith($pointcut);
    }

    /**
     * @return Pointcut
     */
    public static function createEmpty(): self
    {
        return new self(null);
    }

    public function isEmpty(): bool
    {
        return $this->expression === "" || is_null($this->expression);
    }

    public function doesItCut(InterfaceToCall $interfaceToCall, array $endpointAnnotations): bool
    {
        return $this->doesItCutWithPossibleBrackets($this->expression, $endpointAnnotations, $interfaceToCall);
    }

    private function getInBetweenBracketsExpressions(string $expression): array
    {
        preg_match_all("#(\([^\(\)]*\)(\|\||\&\&))#", $expression, $results);

        return $results[2];
    }

    private function doesItCutWithPossibleBrackets(?string $expression, array $endpointAnnotations, InterfaceToCall $interfaceToCall): bool
    {
        if (is_null($expression)) {
            return false;
        }

        $results = $this->getExpressionsDivinedByBrackets($expression);

        if (empty($results)) {
            return $this->doesItCutWithPossibleORs($expression, $endpointAnnotations, $interfaceToCall);
        }

        $expressionsEvaluations = [];
        foreach ($results as $expressionToVerify) {
            $expressionsEvaluations[] = $this->doesItCutWithPossibleORs($expressionToVerify, $endpointAnnotations, $interfaceToCall);
        }

        $inBetweenBracketsExpressions = $this->getInBetweenBracketsExpressions($expression);
        $newExpression                = "";
        for ($index = 0; $index < count($expressionsEvaluations); $index++) {
            if ($index > 0) {
                $newExpression .= $inBetweenBracketsExpressions[$index - 1];
            }

            $newExpression .= $expressionsEvaluations[$index] ? "true" : "false";
        }

        return $this->doesItCutWithPossibleBrackets($newExpression, $endpointAnnotations, $interfaceToCall);
    }

    private function doesItCutWithPossibleORs(string $expressionToVerify, array $endpointAnnotations, InterfaceToCall $interfaceToCall): bool
    {
        $multipleExpression = explode("||", $expressionToVerify);

        foreach ($multipleExpression as $possibleEndExpressions) {
            if ($this->doesItCutPossibleANDs($possibleEndExpressions, $endpointAnnotations, $interfaceToCall)) {
                return true;
            }
        }

        return false;
    }

    private function doesItCutPossibleANDs(string $expressionToVerify, array $endpointAnnotations, InterfaceToCall $interfaceToCall): bool
    {
        $expressions = explode("&&", $expressionToVerify);
        foreach ($expressions as $expression) {
            if (!$this->doesItCutThisExpression($expression, $endpointAnnotations, $interfaceToCall)) {
                return false;
            }
        }

        return true;
    }

    private function doesItCutThisExpression(mixed $expression, array $endpointAnnotations, InterfaceToCall $interfaceToCall): bool
    {
        if ($expression === "true") {
            return true;
        }
        if ($expression === "false") {
            return false;
        }

        if (TypeDescriptor::isItTypeOfExistingClassOrInterface($expression)) {
            $classDefinition = ClassDefinition::createFor(TypeDescriptor::create($expression));
            if ($classDefinition->isAnnotation()) {
                $annotationToCheck = $classDefinition->getClassType();

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
            }

            if ($interfaceToCall->getInterfaceType()->isCompatibleWith($classDefinition->getClassType())) {
                return true;
            }
        }

        if (strpos($expression, "::") !== false) {
            list($class, $method) = explode("::", $expression);

            if ($this->isRelatedClass($class, $interfaceToCall)) {
                if ($interfaceToCall->hasMethodName($method)) {
                    return true;
                }
            }
        }

        if (strpos($expression, "*") !== false) {
            $expression = "#" . str_replace("*", ".*", $expression) . "#";
            $expression = str_replace("\\", "\\\\", $expression);

            return preg_match($expression, $interfaceToCall->getInterfaceName()) === 1;
        }

        return false;
    }

    private function isRelatedClass(string $expression, InterfaceToCall $interfaceToCall): bool
    {
        if (TypeDescriptor::isItTypeOfExistingClassOrInterface($expression)) {
            return $interfaceToCall->getInterfaceType()->isCompatibleWith(TypeDescriptor::create($expression));
        }

        return false;
    }

    private function getExpressionsDivinedByBrackets(?string $expression): array
    {
        if (is_null($expression)) {
            return [];
        }

        preg_match_all('#\(([^\(\)]*)\)#', $expression, $results);

        return $results[1];
    }

    private static function hasConverter(array $parameterConverters, mixed $interfaceParameter): bool
    {
        foreach ($parameterConverters as $parameterConverter) {
            if ($parameterConverter->isHandling($interfaceParameter)) {
                return true;
            }
        }

        return false;
    }

    private static function doesContainAnnotation(TypeDescriptor|UnionTypeDescriptor $type): bool
    {
        foreach ($type->getUnionTypes() as $unionType) {
            if ($unionType->isClassOrInterface() && ClassDefinition::createFor($unionType)->isAnnotation()) {
                return true;
            }
        }

        return false;
    }
}