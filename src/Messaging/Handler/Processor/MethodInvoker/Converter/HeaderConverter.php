<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;

/**
 * Class HeaderArgument
 * @package Ecotone\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class HeaderConverter implements ParameterConverter
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
     * @return HeaderConverter
     */
    public static function create(string $parameterName, string $headerName, bool $isRequired) : self
    {
        return new self($parameterName, $headerName, $isRequired);
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(InterfaceToCall $interfaceToCall, InterfaceParameter $relatedParameter, Message $message, array $endpointAnnotations)
    {
        if (!$this->isRequired && !$message->getHeaders()->containsKey($this->headerName)) {
            return null;
        }

        return $message->getHeaders()->get($this->headerName);
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() == $this->parameterName;
    }
}