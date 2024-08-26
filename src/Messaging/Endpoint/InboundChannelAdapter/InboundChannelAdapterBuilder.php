<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\InboundChannelAdapter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Endpoint\InboundGatewayEntrypoint;
use Ecotone\Messaging\Endpoint\InterceptedChannelAdapterBuilder;
use Ecotone\Messaging\Endpoint\PollingConsumer\MessagePoller\InvocationPollerAdapter;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class InboundChannelAdapterBuilder
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class InboundChannelAdapterBuilder extends InterceptedChannelAdapterBuilder
{
    private string $referenceName;
    private string $requestChannelName;
    private ?object $directObject = null;

    private function __construct(string $requestChannelName, string $referenceName, private InterfaceToCall $interfaceToCall)
    {
        $this->inboundGateway = GatewayProxyBuilder::create($referenceName, InboundGatewayEntrypoint::class, 'executeEntrypoint', $requestChannelName)
            ->withAnnotatedInterface($this->interfaceToCall);
        $this->referenceName = $referenceName;
        $this->requestChannelName = $requestChannelName;
    }

    public static function create(string $requestChannelName, string $referenceName, InterfaceToCall $interfaceToCall): self
    {
        return new self($requestChannelName, $referenceName, $interfaceToCall);
    }

    public static function createWithDirectObject(string $requestChannelName, $objectToInvoke, InterfaceToCall $interfaceToCall): self
    {
        $self = new self($requestChannelName, '', $interfaceToCall);
        $self->directObject = $objectToInvoke;

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointId(): string
    {
        return $this->endpointId;
    }

    /**
     * @param string $endpointId
     * @return InboundChannelAdapterBuilder
     */
    public function withEndpointId(string $endpointId): self
    {
        $this->endpointId = $endpointId;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $this->interfaceToCall;
    }

    /**
     * @inheritDoc
     */
    public function withEndpointAnnotations(iterable $endpointAnnotations): self
    {
        $this->inboundGateway->withEndpointAnnotations($endpointAnnotations);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointAnnotations(): array
    {
        return $this->inboundGateway->getEndpointAnnotations();
    }

    /**
     * @inheritDoc
     */
    public function getRequiredInterceptorNames(): iterable
    {
        return $this->inboundGateway->getRequiredInterceptorNames();
    }

    /**
     * @inheritDoc
     */
    public function withRequiredInterceptorNames(iterable $interceptorNames): self
    {
        $this->inboundGateway->withRequiredInterceptorNames($interceptorNames);

        return $this;
    }

    protected function withContinuesPolling(): bool
    {
        return false;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        Assert::notNullAndEmpty($this->endpointId, "Endpoint Id for inbound channel adapter can't be empty");

        if (! $this->interfaceToCall->hasNoParameters()) {
            throw InvalidArgumentException::create("{$this->interfaceToCall} for InboundChannelAdapter should not have any parameters");
        }

        $objectReference = $this->directObject ?: new Reference($this->referenceName);
        $methodName = $this->interfaceToCall->getMethodName();
        if ($this->interfaceToCall->hasReturnTypeVoid()) {
            if ($this->requestChannelName !== NullableMessageChannel::CHANNEL_NAME) {
                throw InvalidArgumentException::create("{$this->interfaceToCall} for InboundChannelAdapter should not be void, if channel name is not nullChannel");
            }

            $objectReference = new Definition(PassThroughService::class, [$objectReference, $methodName]);
            $methodName = 'execute';
        }

        return new Definition(InvocationPollerAdapter::class, [
            $objectReference,
            $methodName,
        ]);
    }
}
