<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class ServiceReferenceParameterConverter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageToReferenceServiceParameterConverter implements MessageToParameterConverter
{
    /**
     * @var string
     */
    private $parameterName;
    /**
     * @var object
     */
    private $referenceService;

    /**
     * ServiceReferenceParameterConverter constructor.
     * @param string $parameterName
     * @param object $serviceReference
     */
    private function __construct(string $parameterName, $serviceReference)
    {
        $this->parameterName = $parameterName;

        $this->initialize($serviceReference);
    }

    /**
     * @param string $parameterName
     * @param $serviceReference
     * @return MessageToReferenceServiceParameterConverter
     */
    public static function create(string $parameterName, $serviceReference) : self
    {
        return new self($parameterName, $serviceReference);
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message)
    {
        return $this->referenceService;
    }

    /**
     * @inheritDoc
     */
    public function isHandling(\ReflectionParameter $reflectionParameter): bool
    {
        return $reflectionParameter->getName() == $this->parameterName;
    }

    /**
     * @param object $serviceReference
     */
    private function initialize($serviceReference) : void
    {
        Assert::isObject($serviceReference, "Reference must be object for " . self::class);

        $this->referenceService = $serviceReference;
    }
}