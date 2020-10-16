<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Builder\Annotation;
use Ecotone\Messaging\Annotation\Parameter\Value;

/**
 * Class ValueTestBuilder
 * @package Test\Ecotone\Messaging\Builder\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ValueAnnotationTestCaseBuilder
{
    private string $parameterName;
    private string $value;

    /**
     * ExpressionTestBuilder constructor.
     * @param string $parameterName
     * @param string $value
     */
    private function __construct(string $parameterName, $value)
    {
        $this->parameterName = $parameterName;
        $this->value = $value;
    }

    /**
     * @param string $parameterName
     * @param string $value
     * @return ValueAnnotationTestCaseBuilder
     */
    public static function create(string $parameterName, $value) : self
    {
        return new self($parameterName, $value);
    }

    /**
     * @return Value
     */
    public function build()
    {
        $annotation = new Value();
        $annotation->parameterName = $this->parameterName;
        $annotation->value = $this->value;

        return $annotation;
    }
}