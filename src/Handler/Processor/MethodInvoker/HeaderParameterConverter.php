<?php

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\MethodParameterConverter;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class HeaderArgument
 * @package SimplyCodedSoftware\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderParameterConverter implements MethodParameterConverter
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
     * @return HeaderParameterConverter
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