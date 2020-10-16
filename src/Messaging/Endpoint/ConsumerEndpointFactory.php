<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\SubscribableChannel;
use Ecotone\Messaging\Support\Assert;

/**
 * Class ConsumerEndpointFactory - Responsible for creating consumers from builders
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConsumerEndpointFactory
{
    private \Ecotone\Messaging\Handler\ChannelResolver $channelResolver;
    private array $consumerFactories;
    private \Ecotone\Messaging\Handler\ReferenceSearchService $referenceSearchService;
    private array $pollingMetadataMessageHandlers;

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
     * @param MessageChannelBuilder[] $messageChannelBuilders
     * @return ConsumerLifecycle
     * @throws MessagingException
     */
    public function createForMessageHandler(MessageHandlerBuilder $messageHandlerBuilder, array $messageChannelBuilders) : ConsumerLifecycle
    {
        foreach ($this->consumerFactories as $consumerFactory) {
            Assert::keyExists($messageChannelBuilders, $messageHandlerBuilder->getInputMessageChannelName(), "Missing channel with name {$messageHandlerBuilder->getInputMessageChannelName()} for {$messageHandlerBuilder}");
            if ($consumerFactory->isSupporting($messageHandlerBuilder, $messageChannelBuilders[$messageHandlerBuilder->getInputMessageChannelName()])) {

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