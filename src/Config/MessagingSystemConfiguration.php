<?php

namespace SimplyCodedSoftware\Messaging\Config;

use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerEndpointFactory;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerFactory;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class MessagingSystemConfiguration
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MessagingSystemConfiguration implements Configuration
{
    /**
     * @var NamedMessageChannel[]
     */
    private $namedChannels = [];
    /**
     * @var MessageHandlerBuilder[]
     */
    private $messageHandlerBuilders = [];
    /**
     * @var ConsumerFactory[]
     */
    private $consumerFactories = [];
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;
    /**
     * @var ModuleConfigurationRetrievingService
     */
    private $moduleConfigurationRetrievingService;

    /**
     * Only one instance at time
     *
     * MessagingSystemConfiguration constructor.
     * @param ReferenceSearchService $referenceSearchService
     * @param ModuleConfigurationRetrievingService $moduleConfigurationRetrievingService
     */
    private function __construct(ReferenceSearchService $referenceSearchService, ModuleConfigurationRetrievingService $moduleConfigurationRetrievingService)
    {
        $this->referenceSearchService = $referenceSearchService;
        $this->moduleConfigurationRetrievingService = $moduleConfigurationRetrievingService;
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param ModuleConfigurationRetrievingService $moduleConfigurationRetrievingService
     * @return MessagingSystemConfiguration
     */
    public static function prepare(ReferenceSearchService $referenceSearchService, ModuleConfigurationRetrievingService $moduleConfigurationRetrievingService) : self
    {
        return new self($referenceSearchService, $moduleConfigurationRetrievingService);
    }

    /**
     * @inheritDoc
     */
    public function getReferenceSearchService(): ReferenceSearchService
    {
        return $this->referenceSearchService;
    }

    /**
     * @param MessageChannelBuilder $messageChannelBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerMessageChannel(MessageChannelBuilder $messageChannelBuilder): self
    {
        $this->namedChannels[] = NamedMessageChannel::create($messageChannelBuilder->getMessageChannelName(), $messageChannelBuilder->build());

        return $this;
    }

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerMessageHandler(MessageHandlerBuilder $messageHandlerBuilder) : self
    {
        $this->messageHandlerBuilders[] = $messageHandlerBuilder;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerConsumer(ConsumerBuilder $consumerBuilder): MessagingSystemConfiguration
    {
        // TODO: Implement registerConsumer() method.
    }

    /**
     * @inheritDoc
     */
    public function registerConsumerFactory(ConsumerFactory $consumerFactory): MessagingSystemConfiguration
    {
        $this->consumerFactories[] = $consumerFactory;

        return $this;
    }

    /**
     * Initialize messaging system from current configuration
     *
     * @return MessagingSystem
     */
    public function buildMessagingSystemFromConfiguration() : MessagingSystem
    {
        $channelResolver = InMemoryChannelResolver::create($this->namedChannels);
        $consumerEndpointFactory = new ConsumerEndpointFactory($channelResolver, $this->referenceSearchService, $this->consumerFactories);
        $consumers = [];

        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            $consumers[] = $consumerEndpointFactory->create($messageHandlerBuilder);
        }

        foreach ($this->moduleConfigurationRetrievingService->findAllModuleConfigurations() as $moduleMessagingConfiguration) {
            $moduleMessagingConfiguration->registerWithin($this);
        }

        return MessagingSystem::create($consumers, $channelResolver);
    }

    /**
     * Only one instance at time
     *
     * @internal
     */
    private function __clone()
    {

    }
}