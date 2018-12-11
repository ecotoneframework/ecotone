<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class MessageArgument
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class MessageConverter implements ParameterConverter
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
     * @return MessageConverter
     */
    public static function create(string $parameterName) : self
    {
        return new self($parameterName);
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(InterfaceParameter $relatedParameter, Message $message)
    {
        return $message;
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() == $this->parameterName;
    }
}