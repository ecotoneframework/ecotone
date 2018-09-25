<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class MessageToStaticValueParameterConverter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class ValueConverter implements ParameterConverter
{
    /**
     * @var string
     */
    private $parameterName;
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
    public function getArgumentFrom(InterfaceParameter $relatedParameter, Message $message)
    {
        return $this->staticValue;
    }
}