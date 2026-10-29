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
 * Class ReferenceBuilder
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class ReferenceBuilder implements ParameterConverterBuilder
{
    private function __construct(
        private string $parameterName,
        private string $referenceServiceName,
        private ?string $expression
    ) {
    }

    public static function create(string $parameterName, string $referenceServiceName, ?string $expression = null): self
    {
        return new self($parameterName, $referenceServiceName, $expression);
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
        return new Definition(ReferenceConverter::class, [
            new Reference(ExpressionEvaluationService::REFERENCE),
            new Reference($this->referenceServiceName),
            $this->expression,
        ]);
    }
}
