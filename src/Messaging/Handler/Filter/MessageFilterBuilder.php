<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Filter;

use Ecotone\Messaging\Config\Container\ChannelReference;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\InterceptedMessageProcessorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class MessageFilterBuilder
 * @package Ecotone\Messaging\Handler\Filter
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class MessageFilterBuilder implements InterceptedMessageProcessorBuilder
{
    /**
     * @var ParameterConverterBuilder[]
     */
    private array $parameterConverters = [];
    private string|object $referenceNameOrObject;
    private InterfaceToCallReference $interfaceToCallReference;
    private ?string $discardChannelName = null;
    private bool $throwExceptionOnDiscard = false;

    private function __construct(string|object $referenceName, InterfaceToCallReference $interfaceToCall)
    {
        $this->referenceNameOrObject     = $referenceName;
        $this->interfaceToCallReference        = $interfaceToCall;
    }

    public static function createWithReferenceName(string $referenceName, InterfaceToCallReference $interfaceToCall): self
    {
        return new self($referenceName, $interfaceToCall);
    }

    /**
     * @param bool|null $defaultResultWhenHeaderIsMissing When no presented exception will be thrown on missing header
     */
    public static function createBoolHeaderFilter(string $headerName, ?bool $defaultResultWhenHeaderIsMissing = null): self
    {
        return new self(
            new BoolHeaderBasedFilter($headerName, $defaultResultWhenHeaderIsMissing),
            InterfaceToCallReference::create(BoolHeaderBasedFilter::class, 'filter')
        );
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(): InterfaceToCallReference
    {
        return $this->interfaceToCallReference;
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

    public function compile(MessagingContainerBuilder $builder, array $aroundInterceptors = []): Definition
    {
        $interfaceToCall = $builder->getInterfaceToCall($this->interfaceToCallReference);
        if (! $interfaceToCall->hasReturnValueBoolean()) {
            throw InvalidArgumentException::create("Object with reference {$interfaceToCall->getInterfaceName()} should return bool for method {$interfaceToCall->getMethodName()} while using Message Filter");
        }

        return MethodInvokerBuilder::create(
            $this->referenceNameOrObject,
            $this->interfaceToCallReference,
            $this->parameterConverters,
        )
            ->withResultToMessageConverter(new Definition(MessageFilter::class, [
                $this->discardChannelName ? new ChannelReference($this->discardChannelName) : null,
                $this->throwExceptionOnDiscard,
            ]))
            ->compile($builder, $aroundInterceptors);
    }
}
