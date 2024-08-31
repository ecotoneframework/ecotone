<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\InterceptedMessageProcessorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvokerProcessor;

use function is_string;

/**
 * licence Apache-2.0
 */
class MethodInvokerBuilder implements InterceptedMessageProcessorBuilder
{
    private bool $shouldPassTroughMessageIfVoid = false;
    private bool $changeHeaders = false;

    private ?CompilableBuilder $resultToMessageConverter = null;

    /**
     * @param array<ParameterConverterBuilder> $methodParametersConverterBuilders
     */
    private function __construct(
        private object|string $reference,
        private InterfaceToCallReference $interfaceToCallReference,
        private array $methodParametersConverterBuilders = [],
    ) {
    }

    public static function create(object|string $definition, InterfaceToCallReference $interfaceToCallReference, array $methodParametersConverterBuilders = []): self
    {
        return new self($definition, $interfaceToCallReference, $methodParametersConverterBuilders);
    }

    public function withPassTroughMessageIfVoid(bool $shouldPassTroughMessageIfVoid): self
    {
        $this->shouldPassTroughMessageIfVoid = $shouldPassTroughMessageIfVoid;

        return $this;
    }

    public function withChangeHeaders(bool $changeHeaders): self
    {
        $this->changeHeaders = $changeHeaders;

        return $this;
    }

    public function withResultToMessageConverter(CompilableBuilder $compilableBuilder): self
    {
        $this->resultToMessageConverter = $compilableBuilder;

        return $this;
    }

    public function compile(MessagingContainerBuilder $builder, array $aroundInterceptors = []): Definition|Reference
    {
        $interfaceToCall = $builder->getInterfaceToCall($this->interfaceToCallReference);

        $messageConverter = match (true) {
            $this->resultToMessageConverter !== null => $this->resultToMessageConverter->compile($builder),
            $this->shouldPassTroughMessageIfVoid && $interfaceToCall->getReturnType()->isVoid() => new Definition(PassthroughMessageConverter::class),
            $this->changeHeaders => new Definition(HeaderResultMessageConverter::class, [(string) $interfaceToCall]),
            default => new Definition(PayloadResultMessageConverter::class, [
                $interfaceToCall->getReturnType(),
            ])
        };

        return new Definition(MethodInvokerProcessor::class, [
            $this->compileWithoutProcessor($builder, $aroundInterceptors),
            $messageConverter,
        ]);
    }

    public function compileWithoutProcessor(MessagingContainerBuilder $builder, array $aroundInterceptors = []): Definition
    {
        $interfaceToCall = $builder->getInterfaceToCall($this->interfaceToCallReference);

        if ($this->reference instanceof Definition
            && is_a($this->reference->getClassName(), MethodInvokerObjectResolver::class, true)) {
            $objectToInvokeOnResolver = $this->reference;
        } else {
            $objectToInvokeOnResolver = new Definition(MethodInvokerStaticObjectResolver::class, [
                is_string($this->reference) && ! $interfaceToCall->isStaticallyCalled() ? Reference::to($this->reference) : $this->reference,
            ]);
        }

        $parameterConvertersBuilders = MethodArgumentsFactory::createDefaultMethodParameters($interfaceToCall, $this->methodParametersConverterBuilders);
        $parameterConverters = array_map(
            fn (ParameterConverterBuilder $parameterConverterBuilder) => $parameterConverterBuilder->compile($interfaceToCall),
            $parameterConvertersBuilders
        );

        return new Definition(MethodInvoker::class, [
            $objectToInvokeOnResolver,
            $interfaceToCall->getMethodName(),
            $parameterConverters,
            $interfaceToCall->getInterfaceParametersNames(),
            $aroundInterceptors,
        ]);
    }

    public function getInterceptedInterface(): InterfaceToCallReference
    {
        return $this->interfaceToCallReference;
    }
}
