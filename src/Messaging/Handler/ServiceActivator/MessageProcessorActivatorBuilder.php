<?php

namespace Ecotone\Messaging\Handler\ServiceActivator;

use Ecotone\Messaging\Config\Container\ChannelReference;
use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\MethodInterceptorsConfiguration;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\ChainedMessageProcessor;
use Ecotone\Messaging\Handler\Processor\ChainedMessageProcessorBuilder;
use Ecotone\Messaging\Handler\Processor\InterceptedMessageProcessorBuilder;
use Ecotone\Messaging\Handler\RequestReplyProducer;

/**
 * @licence Apache-2.0
 */
class MessageProcessorActivatorBuilder extends InputOutputMessageHandlerBuilder
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
        $interceptedInterface = $this->chainedMessageProcessorBuilder->getInterceptedInterface();
        $interceptorsConfiguration = $interceptedInterface
            ? $builder->getRelatedInterceptors(
                $interceptedInterface,
                $this->getEndpointAnnotations(),
                $this->getRequiredInterceptorNames()
            )
            : MethodInterceptorsConfiguration::createEmpty();

        $name = $this->getInterceptedInterface($builder->getInterfaceToCallRegistry())->toString();
        $processor = $this->chainedMessageProcessorBuilder->compileProcessor($builder, $interceptorsConfiguration);

        return new Definition(
            RequestReplyProducer::class,
            [
                $this->outputMessageChannelName ? new ChannelReference($this->outputMessageChannelName) : null,
                $processor,
                new Reference(ChannelResolver::class),
                $this->isReplyRequired,
                $name,
            ]
        );
    }

    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        $interceptedInterface = $this->chainedMessageProcessorBuilder->getInterceptedInterface();
        return $interceptedInterface ? $interfaceToCallRegistry->getForReference($interceptedInterface) : $interfaceToCallRegistry->getFor(ChainedMessageProcessor::class, 'process');
    }
}
