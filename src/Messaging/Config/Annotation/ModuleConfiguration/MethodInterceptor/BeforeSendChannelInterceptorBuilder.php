<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptor;

use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptorBuilder;

/**
 * licence Apache-2.0
 */
class BeforeSendChannelInterceptorBuilder implements ChannelInterceptorBuilder
{
    public function __construct(
        private MethodInterceptorBuilder $methodInterceptor,
        private string                   $inputChannelName,
        private InterfaceToCallReference $interceptedInterface,
        private array                    $endpointAnnotations,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function relatedChannelName(): string
    {
        return $this->inputChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getPrecedence(): int
    {
        return $this->methodInterceptor->getPrecedence();
    }

    /**
     * @inheritDoc
     */
    public function compile(MessagingContainerBuilder $builder): Definition
    {
        $messageProcessor = $this->methodInterceptor->compileForInterceptedInterface(
            $builder,
            $this->interceptedInterface,
            $this->endpointAnnotations,
        );
        return new Definition(BeforeSendChannelInterceptor::class, [$messageProcessor]);
    }

    public function __toString()
    {
        return "{$this->inputChannelName} {$this->methodInterceptor}";
    }
}
