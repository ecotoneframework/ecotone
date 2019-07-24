<?php

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter;

use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class AllHeadersConverter
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AllHeadersConverter implements ParameterConverter
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * AllHeadersConverter constructor.
     *
     * @param string $parameterName
     */
    public function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(InterfaceToCall $interfaceToCall, InterfaceParameter $relatedParameter, Message $message, array $endpointAnnotations)
    {
        return $message->getHeaders()->headers();
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }
}