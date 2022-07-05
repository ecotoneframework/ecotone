<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;

/**
 * Class MessageToStaticValueParameterConverter
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class ValueConverter implements ParameterConverter
{
    private string $parameterName;
    /**
     * @var mixed
     */
    private $staticValue;

    /**
     * HeaderArgument constructor.
     *
     * @param string $parameterName
     * @param mixed $staticValue
     */
    private function __construct(string $parameterName, $staticValue)
    {
        $this->parameterName = $parameterName;
        $this->staticValue   = $staticValue;
    }

    /**
     * @param string $parameterName
     * @param mixed  $staticValue
     *
     * @return ValueConverter
     */
    public static function createWith(string $parameterName, $staticValue) : self
    {
        return new self($parameterName, $staticValue);
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() == $this->parameterName;
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(InterfaceToCall $interfaceToCall, InterfaceParameter $relatedParameter, Message $message, array $endpointAnnotations)
    {
        return $this->staticValue;
    }
}