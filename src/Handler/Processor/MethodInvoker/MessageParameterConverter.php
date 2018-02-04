<?php

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\MethodParameterConverter;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class MessageArgument
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageParameterConverter implements MethodParameterConverter
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * MessageArgument constructor.
     * @param string $parameterName
     */
    private function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @param string $parameterName
     * @return MessageParameterConverter
     */
    public static function create(string $parameterName) : self
    {
        return new self($parameterName);
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message)
    {
        return $message;
    }

    /**
     * @inheritDoc
     */
    public function isHandling(\ReflectionParameter $reflectionParameter): bool
    {
        return $reflectionParameter->getName() == $this->parameterName;
    }
}