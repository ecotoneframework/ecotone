<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class HeaderArgument
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageToHeaderParameterConverter implements MessageToParameterConverter
{
    /**
     * @var string
     */
    private $headerName;
    /**
     * @var string
     */
    private $parameterName;

    /**
     * HeaderArgument constructor.
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
     * @return MessageToHeaderParameterConverter
     */
    public static function create(string $parameterName, string $headerName) : self
    {
        return new self($parameterName, $headerName);
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message)
    {
        return $message->getHeaders()->get($this->headerName);
    }

    /**
     * @inheritDoc
     */
    public function isHandling(\ReflectionParameter $reflectionParameter): bool
    {
        return $reflectionParameter->getName() == $this->parameterName;
    }
}