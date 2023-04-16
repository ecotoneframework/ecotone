<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Endpoint\TaskExecutorChannelAdapter\TaskExecutorChannelAdapter;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Precedence;
use Ecotone\Messaging\Scheduling\TaskExecutor;

/**
 * Class InterceptedConsumerBuilder
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class InterceptedChannelAdapterBuilder implements ChannelAdapterConsumerBuilder
{
    protected ?string $endpointId = null;
    protected InboundChannelAdapterEntrypoint|GatewayProxyBuilder $inboundGateway;

    /**
     * @inheritDoc
     */
    final public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        $pollingMetadata = $this->withContinuesPolling() ? $pollingMetadata->setFixedRateInMilliseconds(1) : $pollingMetadata;
        $interceptors = InterceptedConsumer::createInterceptorsForPollingMetadata($pollingMetadata);

        foreach ($interceptors as $interceptor) {
            if ($interceptor->isInterestedInPostSend()) {
                $this->addAroundInterceptor(
                    AroundInterceptorReference::createWithDirectObjectAndResolveConverters(
                        $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME),
                        $interceptor,
                        'postSend',
                        Precedence::ASYNCHRONOUS_CONSUMER_INTERCEPTOR_PRECEDENCE,
                        ''
                    )
                );
            }
        }

        $this->inboundGateway->addAroundInterceptor(AcknowledgeConfirmationInterceptor::createAroundInterceptor(
            $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME),
            $pollingMetadata
        ));

        $consumerLifeCycle = TaskExecutorChannelAdapter::createFrom(
            $this->endpointId,
            $pollingMetadata,
            $this->createInboundChannelAdapter($channelResolver, $referenceSearchService, $pollingMetadata)
        );

        if (! $interceptors) {
            return $consumerLifeCycle;
        }

        return new InterceptedConsumer($consumerLifeCycle, $interceptors);
    }

    protected function withContinuesPolling(): bool
    {
        return true;
    }

    abstract protected function createInboundChannelAdapter(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, PollingMetadata $pollingMetadata): TaskExecutor;
}
