<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Builder\Annotation;
use Ecotone\Messaging\Annotation\Parameter\Reference;

/**
 * Class ReferenceTestBuilder
 * @package Test\Ecotone\Messaging\Builder\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceAnnotationTestCaseBuilder
{
    private string $parameterName;
    private string $referenceName;

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