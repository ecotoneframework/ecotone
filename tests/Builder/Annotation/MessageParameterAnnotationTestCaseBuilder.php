<?php
declare(strict_types=1);

namespace Builder\Annotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\MessageParameter;

/**
 * Class MessageParameterTestBuilder
 * @package Builder\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageParameterAnnotationTestCaseBuilder
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
     * @return MessageParameterAnnotationTestCaseBuilder
     */
    public static function create(string $parameterName) : self
    {
        return new self($parameterName);
    }

    /**
     * @return MessageParameter
     */
    public function build()
    {
        $payload = new MessageParameter();
        $payload->parameterName = $this->parameterName;

        return $payload;
    }
}