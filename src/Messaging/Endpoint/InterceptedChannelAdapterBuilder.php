<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Precedence;

/**
 * Class InterceptedConsumerBuilder
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class InterceptedChannelAdapterBuilder implements ChannelAdapterConsumerBuilder
{
    /**
     * @inheritDoc
     */
    final public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        $interceptors = InterceptedConsumer::createInterceptorsForPollingMetadata($pollingMetadata);

        foreach ($interceptors as $interceptor) {
            $this->addAroundInterceptor(
                AroundInterceptorReference::createWithDirectObjectAndResolveConverters(
                    $interceptor,
                    "postSend",
                    Precedence::ASYNCHRONOUS_CONSUMER_INTERCEPTOR_PRECEDENCE,
                    ""
                )
            );
        }
        $consumerLifeCycle = $this->buildAdapter($channelResolver, $referenceSearchService, $pollingMetadata);

        if (!$interceptors) {
            return $consumerLifeCycle;
        }

        return new InterceptedConsumer($consumerLifeCycle, $interceptors);
    }

    /**
     * @param ChannelResolver $channelResolver
     * @param ReferenceSearchService $referenceSearchService
     * @param PollingMetadata $pollingMetadata
     * @return ConsumerLifecycle
     */
    protected abstract function buildAdapter(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, PollingMetadata $pollingMetadata): ConsumerLifecycle;
}