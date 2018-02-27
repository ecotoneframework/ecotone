<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerEndpointFactory;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\MessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;

/**
 * Class MessagingSystemConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MessagingSystemConfiguration implements Configuration
{
    /**
     * @var MessageChannelBuilder[]
     */
    private $channelsBuilders = [];
    /**
     * @var ChannelInterceptorBuilder[]
     */
    private $channelInterceptorBuilders = [];
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
        $this->channelsBuilders[] = $messageChannelBuilder;
        $this->configurationObserver->notifyMessageChannelWasRegistered($messageChannelBuilder->getMessageChannelName(), (string)$messageChannelBuilder);
        $this->requireReferences($messageChannelBuilder->getRequiredReferenceNames());
        
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerChannelInterceptor(ChannelInterceptorBuilder $channelInterceptorBuilder): MessagingSystemConfiguration
    {
        $this->channelInterceptorBuilders[$channelInterceptorBuilder->getImportanceOrder()][] = $channelInterceptorBuilder;
        $this->requireReferences($channelInterceptorBuilder->getRequiredReferenceNames());

        return $this;
    }

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerMessageHandler(MessageHandlerBuilder $messageHandlerBuilder) : self
    {
        $this->requireReferences($messageHandlerBuilder->getRequiredReferenceNames());

        $this->messageHandlerBuilders[] = $messageHandlerBuilder;

        return $this;
    }

    /**
     * @param string[] $referenceNames
     */
    private function requireReferences(array $referenceNames) : void
    {
        foreach ($referenceNames as $requiredReferenceName) {
            if ($requiredReferenceName) {
                $this->configurationObserver->notifyRequiredAvailableReference($requiredReferenceName);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function registerConsumer(ConsumerBuilder $consumerBuilder): MessagingSystemConfiguration
    {
        return $this;
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     */
    public function buildMessagingSystemFromConfiguration(ReferenceSearchService $referenceSearchService) : ConfiguredMessagingSystem
    {
        foreach ($this->moduleConfigurations as $moduleMessagingConfiguration) {
            $moduleMessagingConfiguration->configure($referenceSearchService);
        }

        $channelResolver = $this->createChannelResolver($referenceSearchService);
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
     * @param ReferenceSearchService $referenceSearchService
     * @return ChannelResolver
     */
    private function createChannelResolver(ReferenceSearchService $referenceSearchService) : ChannelResolver
    {
        $channelInterceptorsByImportance = $this->channelInterceptorBuilders;
        arsort($channelInterceptorsByImportance);
        $channelInterceptorsByChannelName = [];

        foreach ($channelInterceptorsByImportance as $channelInterceptors) {
            foreach ($channelInterceptors as $channelInterceptor) {
                $channelInterceptorsByChannelName[$channelInterceptor->relatedChannelName()][] = $channelInterceptor->build($referenceSearchService);
            }
        }

        $channels = [];
        foreach ($this->channelsBuilders as $channelsBuilder) {
            $messageChannel = $channelsBuilder->build($referenceSearchService);
            if (array_key_exists($channelsBuilder->getMessageChannelName(), $channelInterceptorsByChannelName)) {
                $interceptors = $channelInterceptorsByChannelName[$channelsBuilder->getMessageChannelName()];
                if ($messageChannel instanceof PollableChannel) {
                    $messageChannel = new PollableChannelInterceptorAdapter($messageChannel, $interceptors);
                } else {
                    $messageChannel = new EventDrivenChannelInterceptorAdapter($messageChannel, $interceptors);
                }
            }

            $channels[] = NamedMessageChannel::create($channelsBuilder->getMessageChannelName(), $messageChannel);
        }

        return InMemoryChannelResolver::create($channels);
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