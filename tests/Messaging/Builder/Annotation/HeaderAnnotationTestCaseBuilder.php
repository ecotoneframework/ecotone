<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Builder\Annotation;

use Ecotone\Messaging\Annotation\Parameter\Header;

/**
 * Class HeaderTestBuilder
 * @package Test\Ecotone\Messaging\Builder\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderAnnotationTestCaseBuilder
{
    private string $parameterName;
    private string $headerName;
    private bool $isRequired = true;

    /**
     * ExpressionTestBuilder constructor.
     * @param string $parameterName
     * @param string $headerName
     */
    private function __construct(string $parameterName, string $headerName)
    {
        $this->parameterName = $parameterName;
        $this->headerName = $headerName;
    }

    /**
     * @param string $parameterName
     * @param string $headerName
     * @return HeaderAnnotationTestCaseBuilder
     */
    public static function create(string $parameterName, string $headerName) : self
    {
        return new self($parameterName, $headerName);
    }

    /**
     * @param bool $isRequired
     * @return HeaderAnnotationTestCaseBuilder
     */
    public function withRequired(bool $isRequired) : self
    {
        $this->isRequired = $isRequired;

        return $this;
    }

    /**
     * @return Header
     */
    public function build()
    {
        $expressionAnnotation = new Header();
        $expressionAnnotation->parameterName = $this->parameterName;
        $expressionAnnotation->headerName = $this->headerName;

        return $expressionAnnotation;
    }
}