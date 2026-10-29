<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Splitter;

use Ecotone\Messaging\Config\Container\ChannelReference;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\ChainedMessageProcessorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

use function is_string;

/**
 * Class SplitterBuilder
 * @package Ecotone\Messaging\Handler\Splitter
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class SplitterBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters, MessageHandlerBuilderWithOutputChannel
{
    private array $methodParameterConverterBuilders = [];

    private function __construct(private Reference|Definition|DefinedObject $reference, private InterfaceToCallReference $interfaceToCallReference)
    {
    }

    public static function create(string $referenceName, InterfaceToCall $interfaceToCall): self
    {
        return new self(Reference::to($referenceName), InterfaceToCallReference::fromInstance($interfaceToCall));
    }

    public static function createWithDefinition(Definition|string $definition, string $methodName): self
    {
        if (is_string($definition)) {
            $definition = new Definition($definition);
        }
        return new self($definition, new InterfaceToCallReference($definition->getClassName(), $methodName));
    }

    public static function createMessagePayloadSplitter(): self
    {
        return self::createWithDefinition(DirectMessageSplitter::class, 'split');
    }

    /**
     * @inheritDoc
     */
    public function getParameterConverters(): array
    {
        return $this->methodParameterConverterBuilders;
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, ParameterConverterBuilder::class);

        $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor($this->interfaceToCallReference->getClassName(), $this->interfaceToCallReference->getMethodName());
    }

    /**
     * @inheritDoc
     */
    public function compile(MessagingContainerBuilder $builder): Definition
    {
        $interfaceToCall = $builder->getInterfaceToCall($this->interfaceToCallReference);

        if (! $interfaceToCall->doesItReturnIterable()) {
            throw InvalidArgumentException::create("Can't create transformer for {$interfaceToCall}, because method has no return value");
        }

        $interceptorsConfiguration = $builder->getRelatedInterceptors(
            $this->interfaceToCallReference,
            $this->getEndpointAnnotations(),
            $this->requiredInterceptorReferenceNames,
        );

        $processor = ChainedMessageProcessorBuilder::create()
            ->chainInterceptedProcessor(MethodInvokerBuilder::create(
                $interfaceToCall->isStaticallyCalled() ? $this->reference->getId() : $this->reference,
                $this->interfaceToCallReference,
                $this->methodParameterConverterBuilders,
            ))
            ->compileProcessor($builder, $interceptorsConfiguration);

        return new Definition(SplitterHandler::class, [
            $this->outputMessageChannelName ? new ChannelReference($this->outputMessageChannelName) : null,
            $processor,
            new Reference(ChannelResolver::class),
            $interfaceToCall->toString(),
        ]);
    }

    public function __toString()
    {
        return sprintf('Splitter - %s:%s with name `%s` for input channel `%s`', $this->interfaceToCallReference->getClassName(), $this->interfaceToCallReference->getMethodName(), $this->getEndpointId(), $this->getInputMessageChannelName());
    }
}
