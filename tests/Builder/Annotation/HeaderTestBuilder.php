<?php
declare(strict_types=1);

namespace Builder\Annotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Header;

/**
 * Class HeaderTestBuilder
 * @package Builder\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderTestBuilder
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
     * @return HeaderTestBuilder
     */
    public static function create(string $parameterName, string $headerName) : self
    {
        return new self($parameterName, $headerName);
    }

    /**
     * @param bool $isRequired
     * @return HeaderTestBuilder
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