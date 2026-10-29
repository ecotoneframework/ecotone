<?php

namespace Ecotone\Messaging\Handler\ServiceActivator;

use Ecotone\Messaging\Config\Container\ChannelReference;
use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
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
                $chainedMessageProcessorBuilder->getInterceptedInterface()?->getName() ?? '',
            ]
        );
    }

    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        $interceptedInterface = $this->chainedMessageProcessorBuilder->getInterceptedInterface();
        return $interceptedInterface ? $interfaceToCallRegistry->getForReference($interceptedInterface) : $interfaceToCallRegistry->getFor(ChainedMessageProcessor::class, 'process');
    }
}
