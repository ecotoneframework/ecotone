<?php
declare(strict_types=1);

namespace Builder\Annotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Payload;

/**
 * Class PayloadTestBuilder
 * @package Builder\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PayloadAnnotationTestCaseBuilder
{
    /**
     * @var string
     */
    private $parameterName;

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