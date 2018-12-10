<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Chain\ChainMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessagingException;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;
use SimplyCodedSoftware\IntegrationMessaging\SubscribableChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

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
     * @var MessageHandlerBuilderWithOutputChannel[]
     */
    private $preCallInterceptors;
    /**
     * @var MessageHandlerBuilderWithOutputChannel[]
     */
    private $postCallInterceptors;
    /**
     * @var array|PollingMetadata[]
     */
    private $pollingMetadataMessageHandlers;

    /**
     * ConsumerEndpointFactory constructor.
     * @param ChannelResolver $channelResolver
     * @param ReferenceSearchService $referenceSearchService
     * @param MessageHandlerConsumerBuilder[] $consumerFactories
     * @param MessageHandlerBuilderWithOutputChannel[] $preCallInterceptors
     * @param MessageHandlerBuilderWithOutputChannel[] $postCallInterceptors
     * @param PollingMetadata[] $pollingMetadataMessageHandlers
     * @throws MessagingException
     */
    public function __construct(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, array $consumerFactories, array $preCallInterceptors, array $postCallInterceptors, array $pollingMetadataMessageHandlers)
    {
        Assert::allInstanceOfType($preCallInterceptors, MessageHandlerBuilderWithOutputChannel::class);
        Assert::allInstanceOfType($postCallInterceptors, MessageHandlerBuilderWithOutputChannel::class);
        Assert::allInstanceOfType($pollingMetadataMessageHandlers, PollingMetadata::class);
        Assert::allInstanceOfType($consumerFactories, MessageHandlerConsumerBuilder::class);

        $this->channelResolver = $channelResolver;
        $this->consumerFactories = $consumerFactories;
        $this->referenceSearchService = $referenceSearchService;
        $this->preCallInterceptors = $preCallInterceptors;
        $this->postCallInterceptors = $postCallInterceptors;
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
                $preCallInterceptors = $this->findPreCallInterceptorsFor($messageHandlerBuilder);
                $postCallInterceptors = $this->findPostCallInterceptorsFor($messageHandlerBuilder);

                $messageHandlerBuilderToUse = $messageHandlerBuilder;
                if ($preCallInterceptors || $postCallInterceptors) {
                    Assert::isTrue(\assert($messageHandlerBuilder instanceof MessageHandlerBuilderWithOutputChannel), "Problem with {$messageHandlerBuilder->getEndpointId()}. Only Message Handlers with possible output channels can be intercepted.");

                    $messageHandlerBuilderToUse = ChainMessageHandlerBuilder::create()
                        ->withInputChannelName($messageHandlerBuilder->getInputMessageChannelName())
                        ->withOutputMessageChannel($messageHandlerBuilder->getOutputMessageChannelName());
                    
                    foreach ($preCallInterceptors as $preCallInterceptor) {
                        $messageHandlerBuilderToUse->chain($preCallInterceptor);
                    }
                    $messageHandlerBuilderToUse->chain($messageHandlerBuilder);
                    foreach ($postCallInterceptors as $postCallInterceptor) {
                        $messageHandlerBuilderToUse->chain($postCallInterceptor);
                    }
                }

                return $consumerFactory->create(
                    $this->channelResolver,
                    $this->referenceSearchService,
                    $messageHandlerBuilderToUse,
                    array_key_exists($messageHandlerBuilder->getEndpointId(), $this->pollingMetadataMessageHandlers)
                        ? $this->pollingMetadataMessageHandlers[$messageHandlerBuilder->getEndpointId()]
                        : null
                );
            }
        }

        $class = get_class($messageHandlerBuilder);
        throw NoConsumerFactoryForBuilderException::create("No consumer factory found for {$class}");
    }

    /**
     * @param MessageHandlerBuilder $interceptedMessageHandlerBuilder
     * @return MessageHandlerBuilderWithOutputChannel[]
     */
    private function findPreCallInterceptorsFor(MessageHandlerBuilder $interceptedMessageHandlerBuilder) : array
    {
        $preCallInterceptors = [];

        foreach ($this->preCallInterceptors as $preCallInterceptor) {
            if ($preCallInterceptor->getEndpointId() === $interceptedMessageHandlerBuilder->getEndpointId()) {
                $preCallInterceptors[] = $preCallInterceptor;
            }
        }

        return $preCallInterceptors;
    }

    /**
     * @param MessageHandlerBuilder $interceptedMessageHandlerBuilder
     * @return MessageHandlerBuilderWithOutputChannel[]
     */
    private function findPostCallInterceptorsFor(MessageHandlerBuilder $interceptedMessageHandlerBuilder) : array
    {
        $postCallInterceptors = [];

        foreach ($this->postCallInterceptors as $postCallInterceptor) {
            if ($postCallInterceptor->getEndpointId() === $interceptedMessageHandlerBuilder->getEndpointId()) {
                $postCallInterceptors[] = $postCallInterceptor;
            }
        }

        return $postCallInterceptors;
    }
}