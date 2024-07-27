<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\Gateway\GatewayParameterConverterBuilder;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * Class GatewayExpressionBuilder
 * @package Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class GatewayPayloadExpressionBuilder implements GatewayParameterConverterBuilder
{
    private string $parameterName;
    private string $expression;

    /**
     * ExpressionBuilder constructor.
     * @param string $parameterName
     * @param string $expression
     */
    private function __construct(string $parameterName, string $expression)
    {
        $this->parameterName = $parameterName;
        $this->expression = $expression;
    }

    /**
     * @param string $parameterName
     * @param string $expression
     * @return self
     */
    public static function create(string $parameterName, string $expression): self
    {
        return new self($parameterName, $expression);
    }

    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $this->parameterName === $parameter->getName();
    }

    public function compile(MessagingContainerBuilder $builder, InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(
            GatewayPayloadExpressionConverter::class,
            [
                new Reference(ExpressionEvaluationService::REFERENCE),
                $this->parameterName,
                $this->expression,
            ]
        );
    }
}
