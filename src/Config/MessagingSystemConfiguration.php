<?php

namespace SimplyCodedSoftware\Messaging\Config;

use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerEndpointFactory;
use SimplyCodedSoftware\Messaging\Endpoint\MessageHandlerConsumerBuilderFactory;
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
     * @var MessageHandlerConsumerBuilderFactory[]
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
     * @var array|GatewayBuilder[]
     */
    private $gatewayBuilders = [];
    /**
     * @var ConfigurationObserver
     */
    private $configurationObserver;

    /**
     * Only one instance at time
     *
     * MessagingSystemConfiguration constructor.
     * @param ModuleConfigurationRetrievingService $moduleConfigurationRetrievingService
     * @param ConfigurationObserver $configurationObserver
     */
    private function __construct(ModuleConfigurationRetrievingService $moduleConfigurationRetrievingService, ConfigurationObserver $configurationObserver)
    {
        $this->moduleConfigurationRetrievingService = $moduleConfigurationRetrievingService;
        $this->configurationObserver = $configurationObserver;
    }

    /**
     * @param ModuleConfigurationRetrievingService $moduleConfigurationRetrievingService
     * @param ConfigurationObserver $configurationObserver
     * @return MessagingSystemConfiguration
     */
    public static function prepare(ModuleConfigurationRetrievingService $moduleConfigurationRetrievingService, ConfigurationObserver $configurationObserver) : self
    {
        return new self($moduleConfigurationRetrievingService, $configurationObserver);
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
        $this->configurationObserver->notifyMessageChannelWasRegistered($messageChannelBuilder->getMessageChannelName(), (string)$messageChannelBuilder);

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
     * @param GatewayBuilder $gatewayBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerGatewayBuilder(GatewayBuilder $gatewayBuilder) : self
    {
        $this->gatewayBuilders[] = $gatewayBuilder;
        $this->configurationObserver->notifyGatewayBuilderWasRegistered($gatewayBuilder->getReferenceName(), (string)$gatewayBuilder, $gatewayBuilder->getInterfaceName());

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerConsumerFactory(MessageHandlerConsumerBuilderFactory $consumerFactory): MessagingSystemConfiguration
    {
        $this->consumerFactories[] = $consumerFactory;

        return $this;
    }

    /**
     * Initialize messaging system from current configuration
     *
     * @param ReferenceSearchService $referenceSearchService
     * @return ConfiguredMessagingSystem
     */
    public function buildMessagingSystemFromConfiguration(ReferenceSearchService $referenceSearchService) : ConfiguredMessagingSystem
    {
        $this->referenceSearchService = $referenceSearchService;
        $moduleMessagingConfigurations = $this->moduleConfigurationRetrievingService->findAllModuleConfigurations();
        foreach ($moduleMessagingConfigurations as $moduleMessagingConfiguration) {
            $moduleMessagingConfiguration->registerWithin($this);
        }

        $channelResolver = InMemoryChannelResolver::create($this->namedChannels);
        $consumerEndpointFactory = new ConsumerEndpointFactory($channelResolver, $this->referenceSearchService, $this->consumerFactories);
        $consumers = [];

        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            foreach ($messageHandlerBuilder->getRequiredReferenceNames() as $requiredReferenceName) {
                if ($requiredReferenceName) {
                    $this->configurationObserver->notifyRequiredAvailableReference($requiredReferenceName);
                }
            }
            $consumers[] = $consumerEndpointFactory->createForMessageHandler($messageHandlerBuilder);
        }
        $gateways = [];
        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            $gatewayBuilder->setChannelResolver($channelResolver);
            $gateways[] = GatewayReference::createWith($gatewayBuilder);
        }

        $messagingSystem = MessagingSystem::create($consumers, $gateways, $channelResolver);
        $this->configurationObserver->notifyConfigurationWasFinished($messagingSystem);
        foreach ($moduleMessagingConfigurations as $moduleMessagingConfiguration) {
            $moduleMessagingConfiguration->postConfigure($messagingSystem);
        }

        return $messagingSystem;
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