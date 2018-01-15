<?php

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
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
     * @var array|ConsumerFactory[]
     */
    private $consumerFactories;

    /**
     * ConsumerEndpointFactory constructor.
     * @param ChannelResolver $channelResolver
     * @param array $consumerFactories
     */
    public function __construct(ChannelResolver $channelResolver, array $consumerFactories)
    {
        $this->channelResolver = $channelResolver;
        $this->consumerFactories = $consumerFactories;
    }

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return ConsumerLifecycle
     * @throws NoConsumerFactoryForBuilderException
     */
    public function create(MessageHandlerBuilder $messageHandlerBuilder) : ConsumerLifecycle
    {
        $messageHandlerBuilder = $messageHandlerBuilder->setChannelResolver($this->channelResolver);

        foreach ($this->consumerFactories as $consumerFactory) {
            if ($consumerFactory->isSupporting($this->channelResolver, $messageHandlerBuilder)) {
                return $consumerFactory->create($this->channelResolver, $messageHandlerBuilder);
            }
        }

        throw NoConsumerFactoryForBuilderException::create("No consumer factory found for {$messageHandlerBuilder}");
    }
}