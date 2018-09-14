<?php
declare(strict_types=1);

namespace Builder\Annotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Value;

/**
 * Class ValueTestBuilder
 * @package Builder\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ValueAnnotationTestCaseBuilder
{
    /**
     * @var string
     */
    private $parameterName;
    /**
     * @var mixed
     */
    private $value;

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