<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerEndpointFactory;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\MessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class MessagingSystemConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
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
     * @var ModuleMessagingConfiguration[]
     */
    private $moduleConfigurations = [];
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
     * @param ConfigurationVariableRetrievingService $configurationVariableRetrievingService
     * @param ConfigurationObserver $configurationObserver
     */
    private function __construct(ModuleConfigurationRetrievingService $moduleConfigurationRetrievingService, ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ConfigurationObserver $configurationObserver)
    {
        $this->configurationObserver = $configurationObserver;
        $this->initialize($moduleConfigurationRetrievingService, $configurationVariableRetrievingService);
    }

    /**
     * @param ModuleConfigurationRetrievingService $moduleConfigurationRetrievingService
     * @param ConfigurationVariableRetrievingService $configurationVariableRetrievingService
     * @param ConfigurationObserver $configurationObserver
     * @return MessagingSystemConfiguration
     */
    public static function prepare(ModuleConfigurationRetrievingService $moduleConfigurationRetrievingService, ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ConfigurationObserver $configurationObserver) : self
    {
        return new self($moduleConfigurationRetrievingService, $configurationVariableRetrievingService, $configurationObserver);
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
        foreach ($messageHandlerBuilder->getRequiredReferenceNames() as $requiredReferenceName) {
            if ($requiredReferenceName) {
                $this->configurationObserver->notifyRequiredAvailableReference($requiredReferenceName);
            }
        }

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
        $channelResolver = InMemoryChannelResolver::create($this->namedChannels);
        $consumerEndpointFactory = new ConsumerEndpointFactory($channelResolver, $referenceSearchService, $this->consumerFactories);
        $consumers = [];

        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            $consumers[] = $consumerEndpointFactory->createForMessageHandler($messageHandlerBuilder);
        }
        $gateways = [];
        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            $gateways[] = GatewayReference::createWith($gatewayBuilder, $channelResolver);
        }

        $messagingSystem = MessagingSystem::create($consumers, $gateways, $channelResolver);
        $this->configurationObserver->notifyConfigurationWasFinished($messagingSystem);
        foreach ($this->moduleConfigurations as $moduleMessagingConfiguration) {
            $moduleMessagingConfiguration->postConfigure($messagingSystem);
        }

        return $messagingSystem;
    }

    /**
     * @param ModuleConfigurationRetrievingService $moduleConfigurationRetrievingService
     * @param ConfigurationVariableRetrievingService $configurationVariableRetrievingService
     */
    private function initialize(ModuleConfigurationRetrievingService $moduleConfigurationRetrievingService, ConfigurationVariableRetrievingService $configurationVariableRetrievingService) : void
    {
        $moduleMessagingConfigurations = $moduleConfigurationRetrievingService->findAllModuleConfigurations();
        foreach ($moduleMessagingConfigurations as $moduleMessagingConfiguration) {
            $moduleMessagingConfiguration->registerWithin($this, $configurationVariableRetrievingService);
        }

        $this->moduleConfigurations = $moduleMessagingConfigurations;
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