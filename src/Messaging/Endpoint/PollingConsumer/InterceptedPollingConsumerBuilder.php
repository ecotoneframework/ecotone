<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\ChannelReference;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\PollingMetadataReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Endpoint\AcknowledgeConfirmationInterceptor;
use Ecotone\Messaging\Endpoint\InboundChannelAdapterEntrypoint;
use Ecotone\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Precedence;
use Ecotone\Messaging\Scheduling\Clock;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * licence Apache-2.0
 */
abstract class InterceptedPollingConsumerBuilder implements MessageHandlerConsumerBuilder
{
    private array $endpointAnnotations = [];

    /**
     * @inheritDoc
     */
    public function withEndpointAnnotations(array $endpointAnnotations): self
    {
        $this->endpointAnnotations = $endpointAnnotations;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointAnnotations(): array
    {
        return $this->endpointAnnotations;
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(MessageHandlerBuilder $messageHandlerBuilder, MessageChannelBuilder $relatedMessageChannel): bool
    {
        return $relatedMessageChannel->isPollable();
    }

    public function isPollingConsumer(): bool
    {
        return true;
    }

    protected function withContinuesPolling(): bool
    {
        return true;
    }

    abstract protected function compileMessagePoller(MessagingContainerBuilder $builder, MessageHandlerBuilder $messageHandlerBuilder): Definition|Reference;

    public function registerConsumer(MessagingContainerBuilder $builder, MessageHandlerBuilder $messageHandlerBuilder): void
    {
        $endpointId = $messageHandlerBuilder->getEndpointId();
        $requestChannelName = 'internal_inbound_gateway_channel.'.Uuid::uuid4()->toString();
        $connectionChannel = new Definition(DirectChannel::class, [
            $requestChannelName,
            $messageHandlerBuilder->compile($builder),
        ]);
        $builder->register(new ChannelReference($requestChannelName), $connectionChannel);
        $gatewayBuilder = GatewayProxyBuilder::create(
            'handler',
            InboundChannelAdapterEntrypoint::class,
            'executeEntrypoint',
            $requestChannelName
        );
        $gatewayBuilder->withEndpointAnnotations(array_merge(
            $this->endpointAnnotations,
            [new AttributeDefinition(AsynchronousRunningEndpoint::class, [$endpointId])]
        ));
        $gatewayBuilder
            ->addAroundInterceptor($this->getErrorInterceptorReference($builder))
            ->addAroundInterceptor(AcknowledgeConfirmationInterceptor::createAroundInterceptorBuilder($builder->getInterfaceToCallRegistry()));

        $gateway = $gatewayBuilder->compile($builder);

        $consumerRunner = new Definition(InterceptedConsumerRunner::class, [
            $gateway,
            $this->compileMessagePoller($builder, $messageHandlerBuilder),
            new PollingMetadataReference($endpointId),
            new Reference(Clock::class),
            new Reference(LoggerInterface::class),
            new Reference(MessagingEntrypoint::class),
        ]);
        $builder->registerPollingEndpoint($endpointId, $consumerRunner, $this->withContinuesPolling());
    }

    private function getErrorInterceptorReference(MessagingContainerBuilder $builder): AroundInterceptorBuilder
    {
        if (! $builder->has(PollingConsumerErrorChannelInterceptor::class)) {
            $builder->register(PollingConsumerErrorChannelInterceptor::class, new Definition(PollingConsumerErrorChannelInterceptor::class, [
                new Reference(ChannelResolver::class),
                Reference::to(LoggingGateway::class),
            ]));
        }
        return AroundInterceptorBuilder::create(
            PollingConsumerErrorChannelInterceptor::class,
            $builder->getInterfaceToCall(new InterfaceToCallReference(PollingConsumerErrorChannelInterceptor::class, 'handle')),
            Precedence::ERROR_CHANNEL_PRECEDENCE,
        );
    }
}
