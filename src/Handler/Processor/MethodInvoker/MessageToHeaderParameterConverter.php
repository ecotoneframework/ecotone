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
     * @var bool
     */
    private $isRequired;

    /**
     * HeaderArgument constructor.
     *
     * @param string $parameterName
     * @param string $headerName
     * @param bool   $isRequired
     */
    private function __construct(string $parameterName, string $headerName, bool $isRequired)
    {
        $this->parameterName = $parameterName;
        $this->headerName = $headerName;
        $this->isRequired = $isRequired;
    }

    /**
     * @param string $parameterName
     * @param string $headerName
     * @param bool   $isRequired
     *
     * @return MessageToHeaderParameterConverter
     */
    public static function create(string $parameterName, string $headerName, bool $isRequired) : self
    {
        return new self($parameterName, $headerName, $isRequired);
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message)
    {
        if (!$this->isRequired && !$message->getHeaders()->containsKey($this->headerName)) {
            return null;
        }

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