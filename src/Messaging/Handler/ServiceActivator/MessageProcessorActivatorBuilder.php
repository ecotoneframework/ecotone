<?php

namespace Ecotone\Messaging\Handler\ServiceActivator;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\ChannelReference;
use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\ChainedMessageProcessor;
use Ecotone\Messaging\Handler\Processor\ChainedMessageProcessorBuilder;
use Ecotone\Messaging\Handler\Processor\InterceptedMessageProcessorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Handler\RequestReplyProducer;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;

use function get_class;
use function is_string;

/**
 * @licence Apache-2.0
 */
class MessageProcessorActivatorBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    private ChainedMessageProcessorBuilder $chainedMessageProcessorBuilder;

    private function __construct(
        private bool $isReplyRequired = false,
    ) {
        $this->chainedMessageProcessorBuilder = ChainedMessageProcessorBuilder::create();
    }

    public function __clone(): void
    {
        $this->chainedMessageProcessorBuilder = clone $this->chainedMessageProcessorBuilder;
    }

    public static function create(): self
    {
        return new self();
    }

    /**
     * @param Reference|Definition|DefinedObject|object|string $objectToInvokeOn a reference name, a definition, or the object itself
     */
    public static function createForMethod(object|string $objectToInvokeOn, string|InterfaceToCall|InterfaceToCallReference $method): self
    {
        $interfaceToCallReference = match (true) {
            $method instanceof InterfaceToCallReference => $method,
            $method instanceof InterfaceToCall => InterfaceToCallReference::fromInstance($method),
            is_string($objectToInvokeOn) => new InterfaceToCallReference($objectToInvokeOn, $method),
            $objectToInvokeOn instanceof Reference => new InterfaceToCallReference($objectToInvokeOn->getId(), $method),
            $objectToInvokeOn instanceof Definition => new InterfaceToCallReference($objectToInvokeOn->getClassName(), $method),
            default => new InterfaceToCallReference(get_class($objectToInvokeOn), $method),
        };

        return self::createForMethodInvoker(MethodInvokerBuilder::create($objectToInvokeOn, $interfaceToCallReference));
    }

    public static function createForMethodInvoker(MethodInvokerBuilder $methodInvoker): self
    {
        return self::create()->chainInterceptedProcessor($methodInvoker);
    }

    /**
     * @param array<ParameterConverterBuilder> $methodParameterConverterBuilders
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, ParameterConverterBuilder::class);
        $this->methodInvoker()->withMethodParameterConverters($methodParameterConverterBuilders);

        return $this;
    }

    /**
     * @return array<ParameterConverterBuilder>
     */
    public function getParameterConverters(): array
    {
        $methodInvoker = $this->chainedMessageProcessorBuilder->getInterceptedProcessor();

        return $methodInvoker instanceof MethodInvokerBuilder ? $methodInvoker->getMethodParameterConverters() : [];
    }

    private function methodInvoker(): MethodInvokerBuilder
    {
        $methodInvoker = $this->chainedMessageProcessorBuilder->getInterceptedProcessor();
        if (! $methodInvoker instanceof MethodInvokerBuilder) {
            throw ConfigurationException::create('Parameter converters can be set only on a handler created with MessageProcessorActivatorBuilder::createForMethod() or createForMethodInvoker(). This handler was created with create() and chains no MethodInvokerBuilder, so there is no method to convert parameters for. Pass the converters to MethodInvokerBuilder::create() and chain it with chainInterceptedProcessor() instead.');
        }

        return $methodInvoker;
    }

    public function withRequiredReply(bool $isReplyRequired): self
    {
        $this->isReplyRequired = $isReplyRequired;

        return $this;
    }

    public function chain(CompilableBuilder $processor): self
    {
        $this->chainedMessageProcessorBuilder->chain($processor);

        return $this;
    }

    public function chainInterceptedProcessor(InterceptedMessageProcessorBuilder $processor): self
    {
        $this->chainedMessageProcessorBuilder->chainInterceptedProcessor($processor);

        return $this;
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        $chainedMessageProcessorBuilder = $this->chainedMessageProcessorBuilder
            ->withRequiredInterceptorNames($this->getRequiredInterceptorNames())
            ->withEndpointAnnotations($this->getEndpointAnnotations());

        return new Definition(
            RequestReplyProducer::class,
            [
                $this->outputMessageChannelName ? new ChannelReference($this->outputMessageChannelName) : null,
                $chainedMessageProcessorBuilder->compile($builder),
                new Reference(ChannelResolver::class),
                $this->isReplyRequired,
                new Reference(MessageBuilder::class),
                $chainedMessageProcessorBuilder->getInterceptedInterface()?->getName() ?? '',
            ]
        );
    }

    public function __toString(): string
    {
        $interceptedInterface = $this->chainedMessageProcessorBuilder->getInterceptedInterface();
        if ($interceptedInterface === null) {
            return parent::__toString();
        }

        return sprintf('Handler %s::%s with name `%s` for input channel `%s`', $interceptedInterface->getClassName(), $interceptedInterface->getMethodName(), $this->getEndpointId(), $this->getInputMessageChannelName());
    }

    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        $interceptedInterface = $this->chainedMessageProcessorBuilder->getInterceptedInterface();
        return $interceptedInterface ? $interfaceToCallRegistry->getForReference($interceptedInterface) : $interfaceToCallRegistry->getFor(ChainedMessageProcessor::class, 'process');
    }
}
