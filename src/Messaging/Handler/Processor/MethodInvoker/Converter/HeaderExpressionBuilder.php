<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;

/**
 * Class ExpressionBuilder
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class HeaderExpressionBuilder implements ParameterConverterBuilder
{
    private function __construct(private string $parameterName, private string $headerName, private string $expression, private bool $isRequired)
    {
    }

    /**
     * @param string $parameterName
     * @param string $headerName
     * @param string $expression
     * @param bool $isRequired
     * @return HeaderExpressionBuilder
     */
    public static function create(string $parameterName, string $headerName, string $expression, bool $isRequired): self
    {
        return new self($parameterName, $headerName, $expression, $isRequired);
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
        return new Definition(HeaderExpressionConverter::class, [
            Reference::to(ExpressionEvaluationService::REFERENCE),
            $this->headerName,
            $this->expression,
            $this->isRequired,
        ]);
    }
}
