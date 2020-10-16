<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Builder\Annotation;

use Ecotone\Messaging\Annotation\Parameter\Payload;

/**
 * Class PayloadTestBuilder
 * @package Test\Ecotone\Messaging\Builder\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PayloadAnnotationTestCaseBuilder
{
    private string $parameterName;

    /**
     * PayloadTestBuilder constructor.
     * @param string $parameterName
     */
    private function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @param string $parameterName
     * @return PayloadAnnotationTestCaseBuilder
     */
    public static function create(string $parameterName) : self
    {
        return new self($parameterName);
    }

    /**
     * @return Payload
     */
    public function build()
    {
        $payload = new Payload();
        $payload->parameterName = $this->parameterName;

        return $payload;
    }
}