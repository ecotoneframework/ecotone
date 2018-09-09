<?php
declare(strict_types=1);

namespace Builder\Annotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Reference;

/**
 * Class ReferenceTestBuilder
 * @package Builder\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceTestBuilder
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
     * @return ReferenceTestBuilder
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