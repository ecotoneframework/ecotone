<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessagingException;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\SubscribableChannel;

/**
 * Class ConsumerEndpointFactory - Responsible for creating consumers from builders
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConsumerEndpointFactory
{
    /**
     * @var ChannelResolver
     */
    private $channelResolver;
    /**
     * @var array|MessageHandlerConsumerBuilderFactory[]
     */
    private $consumerFactories;
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;

    /**
     * ConsumerEndpointFactory constructor.
     * @param ChannelResolver $channelResolver
     * @param ReferenceSearchService $referenceSearchService
     * @param array $consumerFactories
     */
    public function __construct(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, array $consumerFactories)
    {
        $this->channelResolver = $channelResolver;
        $this->consumerFactories = $consumerFactories;
        $this->referenceSearchService = $referenceSearchService;
    }

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return ConsumerLifecycle
     * @throws NoConsumerFactoryForBuilderException
     * @throws MessagingException
     */
    public function createForMessageHandler(MessageHandlerBuilder $messageHandlerBuilder) : ConsumerLifecycle
    {
        foreach ($this->consumerFactories as $consumerFactory) {
            if ($consumerFactory->isSupporting($this->channelResolver, $messageHandlerBuilder)) {
                return $consumerFactory->create($this->channelResolver, $this->referenceSearchService, $messageHandlerBuilder);
            }
        }

        $class = get_class($messageHandlerBuilder);
        throw NoConsumerFactoryForBuilderException::create("No consumer factory found for {$class}");
    }
}