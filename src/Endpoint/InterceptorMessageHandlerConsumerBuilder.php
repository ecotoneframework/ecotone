<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class InterceptorConsumerBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterceptorMessageHandlerConsumerBuilder implements MessageHandlerConsumerBuilder
{
    /**
     * @var MessageHandlerConsumerBuilder
     */
    private $innerConsumerBuilder;
    /**
     * @var array
     */
    private $preCallInterceptors;
    /**
     * @var array
     */
    private $postCallInterceptors;

    /**
     * InterceptorChannelAdapterConsumerBuilder constructor.
     * @param MessageHandlerConsumerBuilder $innerConsumerBuilder
     * @param array $preCallInterceptors
     * @param array $postCallInterceptors
     */
    public function __construct(MessageHandlerConsumerBuilder $innerConsumerBuilder, array $preCallInterceptors, array $postCallInterceptors)
    {
        $this->innerConsumerBuilder = $innerConsumerBuilder;
        $this->preCallInterceptors = $preCallInterceptors;
        $this->postCallInterceptors = $postCallInterceptors;
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(ChannelResolver $channelResolver, MessageHandlerBuilder $messageHandlerBuilder): bool
    {
        return $this->innerConsumerBuilder->isSupporting($channelResolver, $messageHandlerBuilder);
    }

    /**
     * @inheritDoc
     */
    public function create(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, MessageHandlerBuilder $messageHandlerBuilder): ConsumerLifecycle
    {
        // TODO: Implement create() method.
    }
}