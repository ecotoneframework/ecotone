<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Builder\Annotation;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Reference;

/**
 * Class ReferenceTestBuilder
 * @package Test\SimplyCodedSoftware\Messaging\Builder\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceAnnotationTestCaseBuilder
{
    /**
     * @var string
     */
    private $parameterName;
    /**
     * @var string
     */
    private $referenceName;

    /**
     * ExpressionTestBuilder constructor.
     * @param string $parameterName
     * @param string $referenceName
     */
    private function __construct(string $parameterName, string $referenceName)
    {
        $this->parameterName = $parameterName;
        $this->referenceName = $referenceName;
    }

    /**
     * @param string $parameterName
     * @param string $referenceName
     * @return ReferenceAnnotationTestCaseBuilder
     */
    public static function create(string $parameterName, string $referenceName) : self
    {
        return new self($parameterName, $referenceName);
    }

    /**
     * @return Reference
     */
    public function build()
    {
        $annotation = new Reference();
        $annotation->parameterName = $this->parameterName;
        $annotation->referenceName = $this->referenceName;

        return $annotation;
    }
}