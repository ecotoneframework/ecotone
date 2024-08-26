<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\ServiceActivator;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Support\Assert;

use function get_class;

/**
 * Class ServiceActivatorFactory
 * @package Ecotone\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @deprecated Use MessageProcessorActivatorBuilder instead
 */
/**
 * licence Apache-2.0
 */
final class ServiceActivatorBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    private bool $isReplyRequired = false;
    private array $methodParameterConverterBuilders = [];
    private bool $shouldPassThroughMessage = false;
    private bool $changeHeaders = false;

    /**
     * @param Reference|Definition|DefinedObject $objectToInvokeOn
     */
    private function __construct(private object $objectToInvokeOn, private InterfaceToCallReference $interfaceToCallReference)
    {
    }

    public static function create(string $objectToInvokeOnReferenceName, InterfaceToCall|InterfaceToCallReference|string $interfaceToCallOrReference): self
    {
        if (is_string($interfaceToCallOrReference)) {
            $interfaceToCallOrReference = new InterfaceToCallReference($objectToInvokeOnReferenceName, $interfaceToCallOrReference);
        } elseif ($interfaceToCallOrReference instanceof InterfaceToCall) {
            $interfaceToCallOrReference = InterfaceToCallReference::fromInstance($interfaceToCallOrReference);
        }
        return new self(new Reference($objectToInvokeOnReferenceName), $interfaceToCallOrReference);
    }

    public static function createWithDefinition(Definition $definition, string $methodName): self
    {
        return new self($definition, new InterfaceToCallReference($definition->getClassName(), $methodName));
    }

    public static function createWithDirectReference(object $directObjectReference, string $methodName): self
    {
        return new self($directObjectReference, new InterfaceToCallReference(get_class($directObjectReference), $methodName));
    }

    /**
     * @param bool $isReplyRequired
     * @return ServiceActivatorBuilder
     */
    public function withRequiredReply(bool $isReplyRequired): self
    {
        $this->isReplyRequired = $isReplyRequired;

        return $this;
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
     * If service is void, message will passed through to next channel
     *
     * @param bool $shouldPassThroughMessage
     * @return ServiceActivatorBuilder
     */
    public function withPassThroughMessageOnVoidInterface(bool $shouldPassThroughMessage): self
    {
        $this->shouldPassThroughMessage = $shouldPassThroughMessage;

        return $this;
    }

    public function withChangingHeaders(bool $changeHeaders): self
    {
        $this->changeHeaders = $changeHeaders;

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
    public function getParameterConverters(): array
    {
        return $this->methodParameterConverterBuilders;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        $interfaceToCall = $builder->getInterfaceToCall($this->interfaceToCallReference);
        $newImplementation = MessageProcessorActivatorBuilder::create()
            ->withInputChannelName($this->inputMessageChannelName)
            ->withOutputMessageChannel($this->outputMessageChannelName)
            ->withEndpointId($this->getEndpointId())
            ->withEndpointAnnotations($this->getEndpointAnnotations())
            ->withRequiredInterceptorNames($this->requiredInterceptorReferenceNames)
            ->withRequiredReply($this->isReplyRequired)
            ->chainInterceptedProcessor(
                MethodInvokerBuilder::create(
                    $interfaceToCall->isStaticallyCalled() ? $this->objectToInvokeOn->getId() : $this->objectToInvokeOn,
                    $this->interfaceToCallReference,
                    $this->methodParameterConverterBuilders,
                )
                ->withPassTroughMessageIfVoid($this->shouldPassThroughMessage)
                ->withChangeHeaders($this->changeHeaders)
            );

        return $newImplementation->compile($builder);
    }

    public function __toString()
    {
        return sprintf('Service Activator - %s:%s', $this->interfaceToCallReference->getClassName(), $this->interfaceToCallReference->getMethodName());
    }
}
