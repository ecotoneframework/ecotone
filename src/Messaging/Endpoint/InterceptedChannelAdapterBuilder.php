<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class InterceptedConsumerBuilder
 * @package SimplyCodedSoftware\Messaging\Endpoint
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
                AroundInterceptorReference::createWithDirectObject(
                    "",
                    $interceptor,
                    "postSend",
                    InterceptedConsumer::CONSUMER_PRECEDENCE_INTERCEPTOR,
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