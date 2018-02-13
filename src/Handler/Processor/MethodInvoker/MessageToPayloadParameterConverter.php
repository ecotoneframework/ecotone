<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class PayloadArgument
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageToPayloadParameterConverter implements MessageToParameterConverter
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * PayloadArgument constructor.
     * @param string $parameterName
     */
    private function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    public static function create(string $parameterName)
    {
        return new self($parameterName);
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message)
    {
        return $message->getPayload();
    }

    /**
     * @inheritDoc
     */
    public function isHandling(\ReflectionParameter $reflectionParameter): bool
    {
        return $reflectionParameter->getName() == $this->parameterName;
    }
}