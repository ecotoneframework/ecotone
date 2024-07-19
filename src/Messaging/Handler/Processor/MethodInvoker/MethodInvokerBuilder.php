<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;

use function is_string;

/**
 * licence Apache-2.0
 */
class MethodInvokerBuilder implements CompilableBuilder
{
    private function __construct(
        private object|string $reference,
        private InterfaceToCallReference $interfaceToCallReference,
        private array $methodParametersConverterBuilders = [],
        private array $endpointAnnotations = []
    ) {
    }

    public static function create(object|string $definition, InterfaceToCallReference $interfaceToCallReference, array $methodParametersConverterBuilders = [], array $endpointAnnotations = []): self
    {
        return new self($definition, $interfaceToCallReference, $methodParametersConverterBuilders, $endpointAnnotations);
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        $interfaceToCall = $builder->getInterfaceToCall($this->interfaceToCallReference);
        $methodParameterConverterBuilders = MethodArgumentsFactory::createDefaultMethodParameters($interfaceToCall, $this->methodParametersConverterBuilders, $this->endpointAnnotations, null, false);

        $compiledMethodParameterConverters = [];
        foreach ($methodParameterConverterBuilders as $index => $methodParameterConverterBuilder) {
            $compiledMethodParameterConverters[] = $methodParameterConverterBuilder->compile($builder, $interfaceToCall, $interfaceToCall->getInterfaceParameters()[$index]);
        }
        if (is_string($this->reference)) {
            $reference = $interfaceToCall->isStaticallyCalled() ? $this->reference : new Reference($this->reference);
        } else {
            $reference = $this->reference;
        }

        return new Definition(MethodInvoker::class, [
            $reference,
            $interfaceToCall->getMethodName(),
            $compiledMethodParameterConverters,
            $this->interfaceToCallReference,
            true,
        ]);
    }
}
