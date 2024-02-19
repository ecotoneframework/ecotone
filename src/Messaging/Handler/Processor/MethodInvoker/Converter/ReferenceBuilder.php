<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;

/**
 * Class ReferenceBuilder
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
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

    public function compile(MessagingContainerBuilder $builder, InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(ReferenceConverter::class, [
            new Reference(ExpressionEvaluationService::REFERENCE),
            new Reference($this->referenceServiceName),
            $this->expression,
        ]);
    }
}
