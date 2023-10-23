<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Filter;

use Ecotone\Messaging\Config\Container\ChannelReference;
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
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class MessageFilterBuilder
 * @package Ecotone\Messaging\Handler\Filter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageFilterBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    /**
     * @var ParameterConverterBuilder[]
     */
    private array $parameterConverters = [];
    private string|object $referenceNameOrObject;
    private string|InterfaceToCall $methodNameOrInterface;
    private ?string $discardChannelName = null;
    private bool $throwExceptionOnDiscard = false;

    private function __construct(string|object $referenceName, string|InterfaceToCall $methodName)
    {
        $this->referenceNameOrObject     = $referenceName;
        $this->methodNameOrInterface        = $methodName;
    }

    public static function createWithReferenceName(string $referenceName, InterfaceToCall $interfaceToCall): self
    {
        return new self($referenceName, $interfaceToCall);
    }

    /**
     * @param bool|null $defaultResultWhenHeaderIsMissing When no presented exception will be thrown on missing header
     */
    public static function createBoolHeaderFilter(string $headerName, ?bool $defaultResultWhenHeaderIsMissing = null): self
    {
        return new self(new BoolHeaderBasedFilter($headerName, $defaultResultWhenHeaderIsMissing), 'filter');
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $this->methodNameOrInterface instanceof InterfaceToCall
            ? $this->methodNameOrInterface
            : $interfaceToCallRegistry->getFor($this->referenceNameOrObject, $this->getMethodName());
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        $this->parameterConverters = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getParameterConverters(): array
    {
        return $this->parameterConverters;
    }

    /**
     * @param string $discardChannelName
     *
     * @return MessageFilterBuilder
     */
    public function withDiscardChannelName(string $discardChannelName): self
    {
        $this->discardChannelName = $discardChannelName;

        return $this;
    }

    /**
     * @param bool $throwOnDiscard
     *
     * @return MessageFilterBuilder
     */
    public function withThrowingExceptionOnDiscard(bool $throwOnDiscard): self
    {
        $this->throwExceptionOnDiscard = $throwOnDiscard;

        return $this;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        $messageSelector = is_object($this->referenceNameOrObject) ? $this->referenceNameOrObject : new Reference($this->referenceNameOrObject);

        $messageSelectorClass = $messageSelector instanceof Reference ? $builder->getDefinition($messageSelector)->getClassName() : get_class($messageSelector);
        $interfaceToCallReference = new InterfaceToCallReference($messageSelectorClass, $this->getMethodName());
        $interfaceToCall = $builder->getInterfaceToCall($interfaceToCallReference);
        if (! $interfaceToCall->hasReturnValueBoolean()) {
            throw InvalidArgumentException::create("Object with reference {$messageSelectorClass} should return bool for method {$this->getMethodName()} while using Message Filter");
        }

        $discardChannel = $this->discardChannelName ? new ChannelReference($this->discardChannelName) : null;

        $methodInvoker = MethodInvokerBuilder::create(
            $messageSelector,
            $interfaceToCallReference,
            $this->parameterConverters,
            $this->getEndpointAnnotations()
        )->compile($builder);

        $messageFilterReference = new Definition(MessageFilter::class, [
            $methodInvoker,
            $discardChannel,
            $this->throwExceptionOnDiscard,
        ]);
        $serviceActivatorBuilder = ServiceActivatorBuilder::createWithDefinition(
            $messageFilterReference,
            'handle',
        )
            ->withInputChannelName($this->inputMessageChannelName)
            ->withOutputMessageChannel($this->outputMessageChannelName);

        $serviceActivatorBuilder->orderedAroundInterceptors = $this->orderedAroundInterceptors;

        return $serviceActivatorBuilder->compile($builder);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('Message filter - %s:%s with name `%s` for input channel `%s`', $this->referenceNameOrObject, $this->getMethodName(), $this->getEndpointId(), $this->inputMessageChannelName);
    }

    private function getMethodName(): string|InterfaceToCall
    {
        return $this->methodNameOrInterface instanceof InterfaceToCall
            ? $this->methodNameOrInterface->getMethodName()
            : $this->methodNameOrInterface;
    }
}
