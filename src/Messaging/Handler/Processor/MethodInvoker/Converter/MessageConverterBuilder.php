<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;

/**
 * Class MessageParameterConverterBuilder
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class MessageConverterBuilder implements ParameterConverterBuilder
{
    private function __construct(private string $parameterName)
    {
    }

    /**
     * @param string $parameterName
     * @return MessageConverterBuilder
     */
    public static function create(string $parameterName): self
    {
        return new self($parameterName);
    }

    public function compile(InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(MessageConverter::class);
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }
}
