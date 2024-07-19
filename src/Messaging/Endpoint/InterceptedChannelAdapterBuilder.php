<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\PollingMetadataReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Endpoint\PollingConsumer\InterceptedConsumerRunner;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollingConsumerErrorChannelInterceptor;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Precedence;
use Ecotone\Messaging\Scheduling\Clock;
use Psr\Log\LoggerInterface;

/**
 * Class InterceptedConsumerBuilder
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
abstract class InterceptedChannelAdapterBuilder implements ChannelAdapterConsumerBuilder, CompilableBuilder
{
    protected ?string $endpointId = null;
    protected InboundChannelAdapterEntrypoint|GatewayProxyBuilder $inboundGateway;

    protected function withContinuesPolling(): bool
    {
        return true;
    }

    protected function compileGateway(MessagingContainerBuilder $builder): Definition|Reference|DefinedObject
    {
        $gatewayBuilder = (clone $this->inboundGateway)
            ->addAroundInterceptor($this->getErrorInterceptorReference($builder))
            ->addAroundInterceptor(AcknowledgeConfirmationInterceptor::createAroundInterceptorBuilder($builder->getInterfaceToCallRegistry()));
        return $gatewayBuilder
            ->compile($builder);
    }

    public function registerConsumer(MessagingContainerBuilder $builder): void
    {
        $messagePoller = $this->compile($builder);
        $gateway = $this->compileGateway($builder);
        $consumerRunner = new Definition(InterceptedConsumerRunner::class, [
            $gateway,
            $messagePoller,
            new PollingMetadataReference($this->endpointId),
            new Reference(Clock::class),
            new Reference(LoggerInterface::class),
            new Reference(MessagingEntrypoint::class),
        ]);
        $builder->registerPollingEndpoint($this->endpointId, $consumerRunner);
    }

    private function getErrorInterceptorReference(MessagingContainerBuilder $builder): AroundInterceptorBuilder
    {
        if (! $builder->has(PollingConsumerErrorChannelInterceptor::class)) {
            $builder->register(PollingConsumerErrorChannelInterceptor::class, new Definition(PollingConsumerErrorChannelInterceptor::class, [
                new Reference(ChannelResolver::class),
            ]));
        }
        return AroundInterceptorBuilder::create(
            PollingConsumerErrorChannelInterceptor::class,
            $builder->getInterfaceToCall(new InterfaceToCallReference(PollingConsumerErrorChannelInterceptor::class, 'handle')),
            Precedence::ERROR_CHANNEL_PRECEDENCE,
        );
    }
}
