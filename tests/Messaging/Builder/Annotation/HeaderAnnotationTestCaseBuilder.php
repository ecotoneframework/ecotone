<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Builder\Annotation;

use SimplyCodedSoftware\Messaging\Annotation\Parameter\Header;

/**
 * Class HeaderTestBuilder
 * @package Test\SimplyCodedSoftware\Messaging\Builder\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderAnnotationTestCaseBuilder
{
    /**
     * @var string
     */
    private $parameterName;
    /**
     * @var string
     */
    private $headerName;
    /**
     * @var bool
     */
    private $isRequired = true;

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