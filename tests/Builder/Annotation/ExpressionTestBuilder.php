<?php
declare(strict_types=1);

namespace Builder\Annotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Expression;

/**
 * Class ExpressionTestBuilder
 * @package Builder\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ExpressionTestBuilder
{
    /**
     * @var string
     */
    private $parameterName;
    /**
     * @var string
     */
    private $expression;

    /**
     * ExpressionTestBuilder constructor.
     * @param string $parameterName
     * @param string $expression
     */
    private function __construct(string $parameterName, string $expression)
    {
        $this->parameterName = $parameterName;
        $this->expression = $expression;
    }

    /**
     * @param string $parameterName
     * @param string $expression
     * @return ExpressionTestBuilder
     */
    public static function create(string $parameterName, string $expression) : self
    {
        return new self($parameterName, $expression);
    }

    /**
     * @return Expression
     */
    public function build()
    {
        $expressionAnnotation = new Expression();
        $expressionAnnotation->parameterName = $this->parameterName;
        $expressionAnnotation->expression = $this->expression;

        return $expressionAnnotation;
    }
}