<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ChannelAdapterConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerEndpointFactory;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\MessageHandlerConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithParameterConverters;
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
     * @var PollingMetadata[]
     */
    private $messageHandlerPollingMetadata = [];
    /**
     * @var Module[]
     */
    private $modules = [];
    /**
     * @var ModuleExtension[][]
     */
    private $moduleExtensions = [];
    /**
     * @var array|GatewayBuilder[]
     */
    private $gatewayBuilders = [];
    /**
     * @var MessageHandlerConsumerBuilder[]
     */
    private $consumerFactories = [];
    /**
     * @var ChannelAdapterConsumerBuilder[]
     */
    private $channelAdapters = [];
    /**
     * @var MessageHandlerBuilderWithOutputChannel[]
     */
    private $preCallMethodInterceptors = [];
    /**
     * @var MessageHandlerBuilderWithOutputChannel[]
     */
    private $postCallMethodInterceptors = [];
    /**
     * @var ConfigurationObserver
     */
    private $configurationObserver;

    /**
     * Only one instance at time
     *
     * MessagingSystemConfiguration constructor.
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     * @param ConfigurationObserver $configurationObserver
     */
    private function __construct(ModuleRetrievingService $moduleConfigurationRetrievingService, ConfigurationObserver $configurationObserver)
    {
        $this->initialize($moduleConfigurationRetrievingService, $configurationObserver);
        $this->configurationObserver = $configurationObserver;

        foreach ($this->modules as $module) {
            $module->prepare(
                $this,
                $this->moduleExtensions[$module->getName()],
                $configurationObserver
            );
        }
    }

    /**
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     * @param ConfigurationObserver $configurationObserver
     */
    private function initialize(ModuleRetrievingService $moduleConfigurationRetrievingService, ConfigurationObserver $configurationObserver): void
    {
        $modules = $moduleConfigurationRetrievingService->findAllModuleConfigurations();
        $moduleExtensions = $moduleConfigurationRetrievingService->findAllModuleExtensionConfigurations();
        foreach ($moduleExtensions as $moduleExtension) {
            $this->moduleExtensions[$moduleExtension->getName()][] = $moduleExtension;
            foreach ($moduleExtension->getRequiredReferences() as $requiredReference) {
                $configurationObserver->notifyRequiredAvailableReference($requiredReference->getReferenceName());
            }
        }

        foreach ($modules as $module) {
            foreach ($module->getRequiredReferences() as $requiredReference) {
                $configurationObserver->notifyRequiredAvailableReference($requiredReference->getReferenceName());
            }

            if (!array_key_exists($module->getName(), $this->moduleExtensions)) {
                $this->moduleExtensions[$module->getName()] = [];
            }

            $this->modules[] = $module;
        }
    }

    /**
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     * @return MessagingSystemConfiguration
     */
    public static function prepare(ModuleRetrievingService $moduleConfigurationRetrievingService): self
    {
        return new self($moduleConfigurationRetrievingService, NullObserver::create());
    }

    /**
     * @param ModuleRetrievingService $moduleRetrievingService
     * @param ConfigurationObserver $configurationObserver
     * @return MessagingSystemConfiguration
     */
    public static function prepareWitObserver(ModuleRetrievingService $moduleRetrievingService, ConfigurationObserver $configurationObserver): self
    {
        return new self($moduleRetrievingService, $configurationObserver);
    }

    /**
     * @param PollingMetadata $pollingMetadata
     * @return MessagingSystemConfiguration
     */
    public function registerPollingMetadata(PollingMetadata $pollingMetadata): self
    {
        $this->messageHandlerPollingMetadata[$pollingMetadata->getMessageHandlerName()] = $pollingMetadata;

        return $this;
    }

    /**
     * @param MessageHandlerBuilderWithOutputChannel $methodInterceptor
     * @return MessagingSystemConfiguration
     */
    public function registerPreCallMethodInterceptor(MessageHandlerBuilderWithOutputChannel $methodInterceptor): self
    {
        $this->preCallMethodInterceptors[] = $methodInterceptor;

        return $this;
    }

    /**
     * @param MessageHandlerBuilderWithOutputChannel $methodInterceptor
     * @return MessagingSystemConfiguration
     */
    public function registerPostCallMethodInterceptor(MessageHandlerBuilderWithOutputChannel $methodInterceptor): self
    {
        $this->postCallMethodInterceptors[] = $methodInterceptor;

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
     * @param string[] $referenceNames
     */
    private function requireReferences(array $referenceNames): void
    {
        foreach ($referenceNames as $requiredReferenceName) {
            if ($requiredReferenceName) {
                $this->configurationObserver->notifyRequiredAvailableReference($requiredReferenceName);
            }
        }
    }

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerMessageHandler(MessageHandlerBuilder $messageHandlerBuilder): self
    {
        $this->requireReferences($messageHandlerBuilder->getRequiredReferenceNames());

        if ($messageHandlerBuilder instanceof MessageHandlerBuilderWithParameterConverters) {
            foreach ($messageHandlerBuilder->getParameterConverters() as $parameterConverter) {
                $this->requireReferences($parameterConverter->getRequiredReferences());
            }
        }

        if (!array_key_exists($messageHandlerBuilder->getInputMessageChannelName(), $this->channelsBuilders)) {
            $this->channelsBuilders[$messageHandlerBuilder->getInputMessageChannelName()] = SimpleMessageChannelBuilder::createDirectMessageChannel($messageHandlerBuilder->getInputMessageChannelName());
        }

        $this->messageHandlerBuilders[] = $messageHandlerBuilder;

        return $this;
    }

    /**
     * @param MessageChannelBuilder $messageChannelBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerMessageChannel(MessageChannelBuilder $messageChannelBuilder): self
    {
        $this->channelsBuilders[$messageChannelBuilder->getMessageChannelName()] = $messageChannelBuilder;
        $this->configurationObserver->notifyMessageChannelWasRegistered($messageChannelBuilder->getMessageChannelName(), get_class($messageChannelBuilder));
        $this->requireReferences($messageChannelBuilder->getRequiredReferenceNames());

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerConsumer(ChannelAdapterConsumerBuilder $consumerBuilder): MessagingSystemConfiguration
    {
        $this->channelAdapters[] = $consumerBuilder;
        $this->requireReferences($consumerBuilder->getRequiredReferences());

        return $this;
    }

    /**
     * @param GatewayBuilder $gatewayBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerGatewayBuilder(GatewayBuilder $gatewayBuilder): self
    {
        $this->gatewayBuilders[] = $gatewayBuilder;
        $this->configurationObserver->notifyGatewayBuilderWasRegistered($gatewayBuilder->getReferenceName(), (string)$gatewayBuilder, $gatewayBuilder->getInterfaceName());
        $this->requireReferences($gatewayBuilder->getRequiredReferences());

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerConsumerFactory(MessageHandlerConsumerBuilder $consumerFactory): MessagingSystemConfiguration
    {
        $this->consumerFactories[] = $consumerFactory;

        return $this;
    }

    /**
     * Initialize messaging system from current configuration
     *
     * @param ReferenceSearchService $externalReferenceSearchService
     * @param ConfigurationVariableRetrievingService $configurationVariableRetrievingService
     * @return ConfiguredMessagingSystem
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function buildMessagingSystemFromConfiguration(ReferenceSearchService $externalReferenceSearchService, ConfigurationVariableRetrievingService $configurationVariableRetrievingService): ConfiguredMessagingSystem
    {
        foreach ($this->modules as $module) {
            $module->configure(
                $this,
                $this->moduleExtensions[$module->getName()],
                $configurationVariableRetrievingService,
                $externalReferenceSearchService
            );
        }

        $modulesWithKeysAsNames = [];
        foreach ($this->modules as $module) {
            $modulesWithKeysAsNames[$module->getName()] = $module;
        }
        $referenceSearchService = InMemoryReferenceSearchService::createWithReferenceService($externalReferenceSearchService, $modulesWithKeysAsNames);
        $channelResolver = $this->createChannelResolver($referenceSearchService);
        $gateways = [];
        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            $gatewayReference = GatewayReference::createWith($gatewayBuilder, $referenceSearchService, $channelResolver);
            $gateways[] = $gatewayReference;
            $this->configurationObserver->notifyGatewayWasBuilt($gatewayReference);
        }

        $consumerEndpointFactory = new ConsumerEndpointFactory($channelResolver, $referenceSearchService, $this->consumerFactories, $this->preCallMethodInterceptors, $this->postCallMethodInterceptors, $this->messageHandlerPollingMetadata);
        $consumers = [];

        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            $consumers[] = $consumerEndpointFactory->createForMessageHandler($messageHandlerBuilder);
        }
        foreach ($this->channelAdapters as $channelAdapter) {
            $consumers[] = $channelAdapter->build($channelResolver, $referenceSearchService);
        }

        $messagingSystem = MessagingSystem::create($consumers, $gateways, $channelResolver);
        $this->configurationObserver->notifyConfigurationWasFinished($messagingSystem);
        foreach ($this->modules as $moduleMessagingConfiguration) {
            $moduleMessagingConfiguration->postConfigure($messagingSystem);
        }

        return $messagingSystem;
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return ChannelResolver
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function createChannelResolver(ReferenceSearchService $referenceSearchService): ChannelResolver
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
            $interceptorsForChannel = [];
            foreach ($channelInterceptorsByChannelName as $channelName => $interceptors) {
                $regexChannel = str_replace("*", ".*", $channelName);
                if (preg_match("#^{$regexChannel}$#", $channelsBuilder->getMessageChannelName())) {
                    $interceptorsForChannel = array_merge($interceptorsForChannel, $interceptors);
                }
            }

            if ($messageChannel instanceof PollableChannel) {
                $messageChannel = new PollableChannelInterceptorAdapter($messageChannel, $interceptorsForChannel);
            } else {
                $messageChannel = new EventDrivenChannelInterceptorAdapter($messageChannel, $interceptorsForChannel);
            }

            $channels[] = NamedMessageChannel::create($channelsBuilder->getMessageChannelName(), $messageChannel);
        }

        return InMemoryChannelResolver::create($channels);
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