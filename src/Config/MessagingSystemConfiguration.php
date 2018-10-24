<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\ConversionService;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\ConverterBuilder;
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
     * @var string[]
     */
    private $requiredReferences = [];
    /**
     * @var string[]
     */
    private $registeredGateways = [];
    /**
     * @var ConverterBuilder[]
     */
    private $converterBuilders = [];

    /**
     * Only one instance at time
     *
     * MessagingSystemConfiguration constructor.
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     */
    private function __construct(ModuleRetrievingService $moduleConfigurationRetrievingService)
    {
        $this->initialize($moduleConfigurationRetrievingService);

        foreach ($this->modules as $module) {
            $module->prepare(
                $this,
                $this->moduleExtensions[$module->getName()]
            );
        }
    }

    /**
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     */
    private function initialize(ModuleRetrievingService $moduleConfigurationRetrievingService): void
    {
        $modules = $moduleConfigurationRetrievingService->findAllModuleConfigurations();
        $moduleExtensions = $moduleConfigurationRetrievingService->findAllModuleExtensionConfigurations();
        foreach ($moduleExtensions as $moduleExtension) {
            $this->moduleExtensions[$moduleExtension->getName()][] = $moduleExtension;
            $this->requireReferences($moduleExtension->getRequiredReferences());
        }

        foreach ($modules as $module) {
            $this->requireReferences($module->getRequiredReferences());

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
        return new self($moduleConfigurationRetrievingService);
    }

    /**
     * @param PollingMetadata $pollingMetadata
     * @return MessagingSystemConfiguration
     */
    public function registerPollingMetadata(PollingMetadata $pollingMetadata): self
    {
        $this->messageHandlerPollingMetadata[$pollingMetadata->getEndpointId()] = $pollingMetadata;

        return $this;
    }

    /**
     * @param MessageHandlerBuilderWithOutputChannel $methodInterceptor
     * @return MessagingSystemConfiguration
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function registerPreCallMethodInterceptor(MessageHandlerBuilderWithOutputChannel $methodInterceptor): self
    {
        if (!$methodInterceptor->getEndpointId()) {
            throw ConfigurationException::create("Interceptor {$methodInterceptor} lack of endpoint id");
        }

        $this->preCallMethodInterceptors[] = $methodInterceptor;

        return $this;
    }

    /**
     * @param MessageHandlerBuilderWithOutputChannel $methodInterceptor
     * @return MessagingSystemConfiguration
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function registerPostCallMethodInterceptor(MessageHandlerBuilderWithOutputChannel $methodInterceptor): self
    {
        if (!$methodInterceptor->getEndpointId()) {
            throw ConfigurationException::create("Interceptor {$methodInterceptor} lack of endpoint id");
        }

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
                $this->requiredReferences[] = $requiredReferenceName;
            }
        }
    }

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return MessagingSystemConfiguration
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function registerMessageHandler(MessageHandlerBuilder $messageHandlerBuilder): self
    {
        if (array_key_exists($messageHandlerBuilder->getEndpointId(), $this->messageHandlerBuilders)) {
            throw ConfigurationException::create("Trying to register endpoints with same id. {$messageHandlerBuilder} and {$this->messageHandlerBuilders[$messageHandlerBuilder->getEndpointId()]}");
        }

        $this->requireReferences($messageHandlerBuilder->getRequiredReferenceNames());

        if ($messageHandlerBuilder instanceof MessageHandlerBuilderWithParameterConverters) {
            foreach ($messageHandlerBuilder->getParameterConverters() as $parameterConverter) {
                $this->requireReferences($parameterConverter->getRequiredReferences());
            }
        }

        if (!array_key_exists($messageHandlerBuilder->getInputMessageChannelName(), $this->channelsBuilders)) {
            $this->channelsBuilders[$messageHandlerBuilder->getInputMessageChannelName()] = SimpleMessageChannelBuilder::createDirectMessageChannel($messageHandlerBuilder->getInputMessageChannelName());
        }

        $this->messageHandlerBuilders[$messageHandlerBuilder->getEndpointId()] = $messageHandlerBuilder;

        return $this;
    }

    /**
     * @param MessageChannelBuilder $messageChannelBuilder
     * @return MessagingSystemConfiguration
     */
    public function registerMessageChannel(MessageChannelBuilder $messageChannelBuilder): self
    {
        $this->channelsBuilders[$messageChannelBuilder->getMessageChannelName()] = $messageChannelBuilder;
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
        $this->registeredGateways[$gatewayBuilder->getReferenceName()] = $gatewayBuilder->getInterfaceName();
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
     * @return string[]
     */
    public function getRequiredReferences() : array
    {
        return $this->requiredReferences;
    }

    /**
     * @return string[]
     */
    public function getRegisteredGateways() : array
    {
        return $this->registeredGateways;
    }

    /**
     * @inheritDoc
     */
    public function registerConverter(ConverterBuilder $converterBuilder): MessagingSystemConfiguration
    {
        $this->converterBuilders[] = $converterBuilder;

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