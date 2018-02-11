<?php

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\SubscribableChannel;

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
     */
    public function createForMessageHandler(MessageHandlerBuilder $messageHandlerBuilder) : ConsumerLifecycle
    {
        foreach ($this->consumerFactories as $consumerFactory) {
            if ($consumerFactory->isSupporting($this->channelResolver, $messageHandlerBuilder)) {
                return $consumerFactory->create($this->channelResolver, $this->referenceSearchService, $messageHandlerBuilder);
            }
        }

        throw NoConsumerFactoryForBuilderException::create("No consumer factory found for {$messageHandlerBuilder}");
    }
}