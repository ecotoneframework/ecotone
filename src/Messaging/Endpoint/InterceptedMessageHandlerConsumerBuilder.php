<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ErrorChannelInterceptor;
use SimplyCodedSoftware\Messaging\Handler\InterceptedEndpoint;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class InterceptedMessageHandlerConsumerBuilder
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class InterceptedMessageHandlerConsumerBuilder implements MessageHandlerConsumerBuilder, InterceptedEndpoint
{
    /**
     * @inheritDoc
     */
    final public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder, PollingMetadata $pollingMetadata): ConsumerLifecycle
    {
        $interceptors = InterceptedConsumer::createInterceptorsForPollingMetadata($pollingMetadata);

        foreach ($interceptors as $interceptor) {
            $this->addAroundInterceptor(
                AroundInterceptorReference::createWithDirectObject(
                    "",
                    $interceptor,
                    "postSend",
                    ErrorChannelInterceptor::PRECEDENCE - 100,
                    ""
                )
            );
        }
        $consumerLifeCycle = $this->buildAdapter($channelResolver, $referenceSearchService, $messageHandlerBuilder, $pollingMetadata);

        if (!$interceptors) {
            return $consumerLifeCycle;
        }

        return new InterceptedConsumer($consumerLifeCycle, $interceptors);
    }

    /**
     * @param ChannelResolver $channelResolver
     * @param ReferenceSearchService $referenceSearchService
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @param PollingMetadata $pollingMetadata
     * @return ConsumerLifecycle
     */
    protected abstract function buildAdapter(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder, PollingMetadata $pollingMetadata): ConsumerLifecycle;
}