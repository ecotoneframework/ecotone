<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;

/**
 * Class HeaderBuilder
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class HeaderBuilder implements ParameterConverterBuilder
{
    private function __construct(private string $parameterName, private string $headerName, private bool $isRequired)
    {
    }

    public static function create(string $parameterName, string $headerName): self
    {
        return new self($parameterName, $headerName, true);
    }

    /**
     * @param string $parameterName
     * @param string $headerName
     * @return HeaderBuilder
     */
    public static function createOptional(string $parameterName, string $headerName): self
    {
        return new self($parameterName, $headerName, false);
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }

    public function compile(InterfaceToCall $interfaceToCall): Definition
    {
        $parameter = $interfaceToCall->getParameterWithName($this->parameterName);
        return new Definition(HeaderConverter::class, [
            $parameter->getTypeDescriptor(),
            $parameter->hasDefaultValue()
                ? new Definition(ParameterDefaultValue::class, [$parameter->getDefaultValue()])
                : null,
            $this->headerName,
            $this->isRequired,
            new Reference(ConversionService::REFERENCE_NAME),
        ]);
    }
}
