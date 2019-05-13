<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\SubscribableChannel;
use SimplyCodedSoftware\Messaging\Support\Assert;

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
     * @var array|MessageHandlerConsumerBuilder[]
     */
    private $consumerFactories;
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;
    /**
     * @var array|PollingMetadata[]
     */
    private $pollingMetadataMessageHandlers;

    /**
     * ConsumerEndpointFactory constructor.
     * @param ChannelResolver $channelResolver
     * @param ReferenceSearchService $referenceSearchService
     * @param MessageHandlerConsumerBuilder[] $consumerFactories
     * @param PollingMetadata[] $pollingMetadataMessageHandlers
     * @throws MessagingException
     */
    public function __construct(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, array $consumerFactories, array $pollingMetadataMessageHandlers)
    {
        Assert::allInstanceOfType($pollingMetadataMessageHandlers, PollingMetadata::class);
        Assert::allInstanceOfType($consumerFactories, MessageHandlerConsumerBuilder::class);

        $this->channelResolver = $channelResolver;
        $this->consumerFactories = $consumerFactories;
        $this->referenceSearchService = $referenceSearchService;
        $this->pollingMetadataMessageHandlers = $pollingMetadataMessageHandlers;
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

                return $consumerFactory->build(
                    $this->channelResolver,
                    $this->referenceSearchService,
                    $messageHandlerBuilder,
                    array_key_exists($messageHandlerBuilder->getEndpointId(), $this->pollingMetadataMessageHandlers)
                        ? $this->pollingMetadataMessageHandlers[$messageHandlerBuilder->getEndpointId()]
                        : PollingMetadata::create($messageHandlerBuilder->getEndpointId())
                );
            }
        }

        $class = get_class($messageHandlerBuilder);
        throw NoConsumerFactoryForBuilderException::create("No consumer factory found for {$class}");
    }
}