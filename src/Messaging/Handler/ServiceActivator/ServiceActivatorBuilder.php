<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\ServiceActivator;

use Ecotone\Messaging\Config\Container\ChannelReference;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\AroundInterceptorHandler;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\HandlerReplyProcessor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Handler\Processor\WrapWithMessageBuildProcessor;
use Ecotone\Messaging\Handler\RequestReplyProducer;
use Ecotone\Messaging\Support\Assert;

use function get_class;

use ReflectionException;
use ReflectionMethod;

/**
 * Class ServiceActivatorFactory
 * @package Ecotone\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class ServiceActivatorBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    private bool $isReplyRequired = false;
    private array $methodParameterConverterBuilders = [];
    private bool $shouldPassThroughMessage = false;
    private bool $shouldWrapResultInMessage = true;
    private bool $changeHeaders = false;

    private ?InterfaceToCall $annotatedInterfaceToCall = null;

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
     * @param bool $shouldWrapInMessage
     * @return ServiceActivatorBuilder
     */
    public function withWrappingResultInMessage(bool $shouldWrapInMessage): self
    {
        $this->shouldWrapResultInMessage = $shouldWrapInMessage;

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

    public function withAnnotatedInterface(InterfaceToCall $interfaceToCall): self
    {
        $this->annotatedInterfaceToCall = $interfaceToCall;

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

        $methodInvokerDefinition = MethodInvokerBuilder::create(
            $this->isStaticallyCalled() ? $this->objectToInvokeOn->getId() : $this->objectToInvokeOn,
            $this->interfaceToCallReference,
            $this->methodParameterConverterBuilders,
            $this->getEndpointAnnotations(),
        )->compile($builder);

        if ($this->shouldWrapResultInMessage || $this->changeHeaders) {
            $methodInvokerDefinition = new Definition(WrapWithMessageBuildProcessor::class, [
                $this->interfaceToCallReference,
                $methodInvokerDefinition,
                $this->changeHeaders,
            ]);
        }
        $handlerDefinition = new Definition(RequestReplyProducer::class, [
            $this->outputMessageChannelName ? new ChannelReference($this->outputMessageChannelName) : null,
            $methodInvokerDefinition,
            new Reference(ChannelResolver::class),
            $this->isReplyRequired,
            $this->shouldPassThroughMessage && $interfaceToCall->hasReturnTypeVoid(),
            RequestReplyProducer::REQUEST_REPLY_METHOD,
        ]);
        if ($this->orderedAroundInterceptors) {
            $interceptors = [];
            foreach (AroundInterceptorBuilder::orderedInterceptors($this->orderedAroundInterceptors) as $aroundInterceptorReference) {
                $interceptors[] = $aroundInterceptorReference->compile($builder, $this->getEndpointAnnotations(), $this->annotatedInterfaceToCall ?? $interfaceToCall);
            }

            $handlerDefinition = new Definition(AroundInterceptorHandler::class, [
                $interceptors,
                new Definition(HandlerReplyProcessor::class, [$handlerDefinition]),
            ]);
        }
        return $handlerDefinition;
    }

    /**
     * @return bool
     * @throws ReflectionException
     */
    private function isStaticallyCalled(): bool
    {
        $referenceMethod = new ReflectionMethod($this->interfaceToCallReference->getClassName(), $this->getMethodName());

        if ($referenceMethod->isStatic()) {
            return true;
        }

        return false;
    }

    public function __toString()
    {
        return sprintf('Service Activator - %s:%s', $this->getInterfaceName(), $this->getMethodName());
    }

    private function getMethodName(): string
    {
        return $this->interfaceToCallReference->getMethodName();
    }

    private function getInterfaceName(): string
    {
        return $this->interfaceToCallReference->getClassName();
    }

    public function withAroundInterceptors(array $orderedAroundInterceptors): self
    {
        $this->orderedAroundInterceptors = $orderedAroundInterceptors;

        return $this;
    }
}
