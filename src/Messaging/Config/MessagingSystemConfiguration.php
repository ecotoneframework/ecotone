<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use function array_map;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\AnnotationFinder\AnnotationFinderFactory;
use Ecotone\Lite\Test\TestConfiguration;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\EventDrivenChannelInterceptorAdapter;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\PollableChannelInterceptorAdapter;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModuleRetrievingService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\AsynchronousModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptor\BeforeSendChannelInterceptorBuilder;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\ChannelReference;
use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Compiler\CompilerPass;
use Ecotone\Messaging\Config\Container\Compiler\ContainerImplementation;
use Ecotone\Messaging\Config\Container\Compiler\ModuleConfigurationCompilerPass;
use Ecotone\Messaging\Config\Container\Compiler\RegisterSingletonMessagingServices;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\ContainerConfig;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\GatewayProxyReference;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\Licence\LicenceService;
use Ecotone\Messaging\ConfigurationVariableService;
use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use Ecotone\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Gateway\MessagingEntrypointWithHeadersPropagation;
use Ecotone\Messaging\Handler\Bridge\BridgeBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InterceptedEndpoint;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Logger\LoggingService;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\InterceptorWithPointCut;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptorBuilder;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\UninterruptibleServiceActivator;
use Ecotone\Messaging\Handler\Transformer\RoutingSlipPrepender;
use Ecotone\Messaging\InMemoryConfigurationVariableService;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Config\MessageBusChannel;
use Exception;

use function is_a;

use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;

/**
 * Class Configuration
 * @package Ecotone\Messaging\Config
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
final class MessagingSystemConfiguration implements Configuration
{
    /**
     * @var MessageChannelBuilder[]
     */
    private array $channelBuilders = [];
    /**
     * @var MessageChannelBuilder[]
     */
    private array $defaultChannelBuilders = [];
    /**
     * @var ChannelInterceptorBuilder[]
     */
    private array $channelInterceptorBuilders = [];
    /**
     * @var MessageHandlerBuilder[]
     */
    private array $messageHandlerBuilders = [];
    /**
     * @var PollingMetadata[]
     */
    private array $pollingMetadata = [];
    /** @var GatewayProxyBuilder[] */
    private array $gatewayBuilders = [];
    /**
     * @var MessageHandlerConsumerBuilder[]
     */
    private array $consumerFactories = [];
    /**
     * @var ChannelAdapterConsumerBuilder[]
     */
    private array $channelAdapters = [];
    /**
     * @var MethodInterceptorBuilder[]
     */
    private array $beforeSendInterceptors = [];
    /**
     * @var MethodInterceptorBuilder[]
     */
    private array $beforeCallMethodInterceptors = [];
    /**
     * @var AroundInterceptorBuilder[]
     */
    private array $aroundMethodInterceptors = [];
    /**
     * @var MethodInterceptorBuilder[]
     */
    private array $afterCallMethodInterceptors = [];
    /**
     * @var CompilableBuilder[]
     */
    private array $converterBuilders = [];
    /**
     * @var string[]
     */
    private array $messageConverterReferenceNames = [];
    private ?ModuleReferenceSearchService $moduleReferenceSearchService;
    private array $asynchronousEndpoints = [];
    private ServiceConfiguration $applicationConfiguration;
    /**
     * @var string[]
     */
    private array $requiredConsumerEndpointIds = [];
    /**
     * @var ConsoleCommandConfiguration[]
     */
    private array $consoleCommands = [];

    /**
     * @var array<string, Definition|Reference> $serviceDefinitions
     */
    private array $serviceDefinitions = [];

    private InterfaceToCallRegistry $interfaceToCallRegistry;

    private bool $isRunningForEnterpriseLicence = false;
    /**
     * @var CompilerPass[] $compilerPasses
     */
    private array $compilerPasses = [];

    private ModuleConfigurationCompilerPass $moduleConfigurationCompilerPass;

    private bool $isRunningForTest = false;

    /**
     * @var array<string, string> $requiredReferences Map of referenceId => errorMessage
     */
    private array $requiredReferences = [];

    private ?ContainerInterface $externalContainer = null;

    /**
     * @param object[] $extensionObjects
     */
    private function __construct(ModuleRetrievingService $moduleConfigurationRetrievingService, array $extensionObjects, InterfaceToCallRegistry $preparationInterfaceRegistry, ServiceConfiguration $serviceConfiguration)
    {
        $extensionObjects = array_merge($extensionObjects, $serviceConfiguration->getExtensionObjects());
        $extensionApplicationConfiguration = [];
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof ServiceConfiguration) {
                $extensionApplicationConfiguration[] = $extensionObject;
            }
        }
        $serviceConfiguration = $serviceConfiguration->mergeWith($extensionApplicationConfiguration);

        if (! $serviceConfiguration->getConnectionRetryTemplate()) {
            if ($serviceConfiguration->isProductionConfiguration()) {
                $serviceConfiguration->withConnectionRetryTemplate(
                    RetryTemplateBuilder::exponentialBackoff(1000, 3)
                        ->maxRetryAttempts(5)
                );
            } else {
                $serviceConfiguration->withConnectionRetryTemplate(
                    RetryTemplateBuilder::exponentialBackoff(100, 3)
                        ->maxRetryAttempts(3)
                );
            }
        }

        $this->applicationConfiguration = $serviceConfiguration;
        $this->interfaceToCallRegistry = $preparationInterfaceRegistry;

        $extensionObjects = array_filter(
            $extensionObjects,
            function ($extensionObject) {
                if (is_null($extensionObject)) {
                    return false;
                }

                return ! ($extensionObject instanceof ServiceConfiguration);
            }
        );

        $this->isRunningForTest = ExtensionObjectResolver::contains(TestConfiguration::class, $extensionObjects);

        $extensionObjects[] = $serviceConfiguration;

        if ($serviceConfiguration->getLicenceKey() !== null) {
            (new LicenceService())->validate($serviceConfiguration->getLicenceKey());
            $this->isRunningForEnterpriseLicence = true;
        }

        $this->initialize($moduleConfigurationRetrievingService, $extensionObjects, $serviceConfiguration);
    }

    /**
     * @param string[] $skippedModulesPackages
     */
    private function initialize(ModuleRetrievingService $moduleConfigurationRetrievingService, array $serviceExtensions, ServiceConfiguration $serviceConfiguration): void
    {
        $moduleReferenceSearchService = ModuleReferenceSearchService::createEmpty();

        $modules = $moduleConfigurationRetrievingService->findAllModuleConfigurations($serviceConfiguration->getSkippedModulesPackages());

        /** This will allow to delay all configuration when everything is already added to the MessagingConfiguration */
        $this->moduleConfigurationCompilerPass = new ModuleConfigurationCompilerPass($modules, $serviceExtensions, $serviceConfiguration, $this, $moduleReferenceSearchService);
        $this->moduleReferenceSearchService = $moduleReferenceSearchService;
    }

    public function getInterfaceToCallRegistry(): InterfaceToCallRegistry
    {
        return $this->interfaceToCallRegistry;
    }

    public static function getModuleClassesFor(ServiceConfiguration $serviceConfiguration): array
    {
        $modulesClasses = [];
        foreach (array_diff(array_merge(ModulePackageList::allPackages(), [ModulePackageList::TEST_PACKAGE]), $serviceConfiguration->getSkippedModulesPackages()) as $availablePackage) {
            $modulesClasses = array_merge($modulesClasses, ModulePackageList::getModuleClassesForPackage($availablePackage));
        }

        return array_filter($modulesClasses, fn (string $moduleClassName): bool => class_exists($moduleClassName) || interface_exists($moduleClassName));
    }

    public static function addCorePackage(ServiceConfiguration $serviceConfiguration, bool $enableTestPackage): ServiceConfiguration
    {
        $requiredModules = [ModulePackageList::CORE_PACKAGE];
        $skippedPackages = $serviceConfiguration->getSkippedModulesPackages();
        if ($enableTestPackage) {
            $requiredModules[] = ModulePackageList::TEST_PACKAGE;
        } else {
            $skippedPackages[] = ModulePackageList::TEST_PACKAGE;
        }

        return $serviceConfiguration->withSkippedModulePackageNames(array_diff($skippedPackages, $requiredModules));
    }

    private function prepareAndOptimizeConfiguration(InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $this->verifyEndpointAndChannelNameUniqueness();
        $this->beforeSendInterceptors = $this->orderMethodInterceptors($this->beforeSendInterceptors);
        $this->beforeCallMethodInterceptors = $this->orderMethodInterceptors($this->beforeCallMethodInterceptors);
        $this->aroundMethodInterceptors = $this->orderMethodInterceptors($this->aroundMethodInterceptors);
        $this->afterCallMethodInterceptors = $this->orderMethodInterceptors($this->afterCallMethodInterceptors);

        foreach ($this->channelAdapters as $channelAdapter) {
            $channelAdapter->withEndpointAnnotations(array_merge($channelAdapter->getEndpointAnnotations(), [new AttributeDefinition(AsynchronousRunningEndpoint::class, [$channelAdapter->getEndpointId()])]));
        }

        if ($this->beforeSendInterceptors) {
            /** @var array<string, MethodInterceptorBuilder[]> $beforeSendInterceptorsByChannel */
            $beforeSendInterceptorsByChannel = [];
            foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
                if ($messageHandlerBuilder instanceof MessageHandlerBuilderWithOutputChannel) {
                    $interceptorWithPointCuts = $this->getRelatedInterceptors($this->beforeSendInterceptors, $messageHandlerBuilder->getInterceptedInterface($interfaceToCallRegistry), $messageHandlerBuilder->getEndpointAnnotations(), $messageHandlerBuilder->getRequiredInterceptorNames());
                    foreach ($interceptorWithPointCuts as $interceptorReference) {
                        if (isset($beforeSendInterceptorsByChannel[$messageHandlerBuilder->getInputMessageChannelName()])) {
                            if (in_array($interceptorReference, $beforeSendInterceptorsByChannel[$messageHandlerBuilder->getInputMessageChannelName()], true)) {
                                continue;
                            }
                        }
                        $beforeSendInterceptorsByChannel[$messageHandlerBuilder->getInputMessageChannelName()][] = $interceptorReference;
                        $this->registerChannelInterceptor(new BeforeSendChannelInterceptorBuilder(
                            $interceptorReference,
                            $messageHandlerBuilder->getInputMessageChannelName(),
                            InterfaceToCallReference::fromInstance($messageHandlerBuilder->getInterceptedInterface($interfaceToCallRegistry)),
                            $messageHandlerBuilder->getEndpointAnnotations()
                        ));
                    }
                }
            }
        }

        krsort($this->channelInterceptorBuilders);

        $this->configureDefaultMessageChannels();
        $this->configureAsynchronousEndpoints();

        foreach ($this->requiredConsumerEndpointIds as $requiredConsumerEndpointId) {
            if (! array_key_exists($requiredConsumerEndpointId, $this->messageHandlerBuilders) && ! array_key_exists($requiredConsumerEndpointId, $this->channelAdapters)) {
                throw ConfigurationException::create("Consumer with id {$requiredConsumerEndpointId} has no configuration defined. Define consumer configuration and retry.");
            }
        }
        foreach ($this->pollingMetadata as $pollingMetadata) {
            if (! $this->hasMessageHandlerWithName($pollingMetadata) && ! $this->hasChannelAdapterWithName($pollingMetadata)) {
                throw ConfigurationException::create("Trying to register polling meta data for non existing endpoint {$pollingMetadata->getEndpointId()}. Verify if there is any asynchronous endpoint with such name.");
            }
            if ($pollingMetadata->getErrorChannelName() && ! isset($this->channelBuilders[$pollingMetadata->getErrorChannelName()])) {
                throw ConfigurationException::create("Trying to register polling meta data for non existing error channel `{$pollingMetadata->getErrorChannelName()}` for endpoint `{$pollingMetadata->getEndpointId()}`");
            }
        }

        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            $gatewayBuilder->withMessageConverters($this->messageConverterReferenceNames);
        }
    }

    /**
     * @template T of MethodInterceptorBuilder|AroundInterceptorBuilder
     * @param array<T> $interceptors
     * @param InterfaceToCall $interceptedInterface
     * @param AttributeDefinition[] $endpointAnnotations
     * @param string[] $requiredInterceptorNames
     *
     * @return array<T>
     * @throws MessagingException
     */
    private function getRelatedInterceptors(array $interceptors, InterfaceToCall $interceptedInterface, iterable $endpointAnnotations, iterable $requiredInterceptorNames): iterable
    {
        Assert::allInstanceOfType($endpointAnnotations, AttributeDefinition::class);

        $relatedInterceptors = [];
        foreach ($requiredInterceptorNames as $requiredInterceptorName) {
            if (! $this->doesInterceptorWithNameExists($requiredInterceptorName)) {
                throw ConfigurationException::create("Can't find interceptor with name {$requiredInterceptorName} for {$interceptedInterface}");
            }
        }

        $endpointAnnotationsInstances = array_map(
            fn (AttributeDefinition $attributeDefinition) => $attributeDefinition->instance(),
            $endpointAnnotations
        );
        foreach ($interceptors as $interceptor) {
            foreach ($requiredInterceptorNames as $requiredInterceptorName) {
                if ($interceptor->hasName($requiredInterceptorName)) {
                    $relatedInterceptors[] = $interceptor;
                    break;
                }
            }

            if ($interceptor->doesItCutWith($interceptedInterface, $endpointAnnotationsInstances)) {
                $relatedInterceptors[] = $interceptor;
            }
        }

        return $relatedInterceptors;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function doesInterceptorWithNameExists(string $name): bool
    {
        /** @var InterceptorWithPointCut $interceptor */
        foreach (array_merge($this->aroundMethodInterceptors, $this->beforeCallMethodInterceptors, $this->afterCallMethodInterceptors) as $interceptor) {
            if ($interceptor->hasName($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function registerChannelInterceptor(ChannelInterceptorBuilder $channelInterceptorBuilder): Configuration
    {
        $this->channelInterceptorBuilders[$channelInterceptorBuilder->getPrecedence()][] = $channelInterceptorBuilder;

        return $this;
    }

    /**
     * @return void
     */
    private function configureAsynchronousEndpoints(): void
    {
        foreach ($this->asynchronousEndpoints as $targetEndpointId => $asynchronousMessageChannels) {
            $asynchronousMessageChannel = array_shift($asynchronousMessageChannels);
            if (! isset($this->channelBuilders[$asynchronousMessageChannel]) && ! isset($this->defaultChannelBuilders[$asynchronousMessageChannel])) {
                throw ConfigurationException::create("Registered asynchronous endpoint `{$targetEndpointId}`, however channel configuration for `{$asynchronousMessageChannel}` was not provided.");
            }

            $foundEndpoint = false;
            foreach ($this->messageHandlerBuilders as $key => $messageHandlerBuilder) {
                if ($messageHandlerBuilder->getEndpointId() === $targetEndpointId) {
                    $busRoutingChannel = $messageHandlerBuilder->getInputMessageChannelName();
                    $handlerExecutionChannel        = AsynchronousModule::getHandlerExecutionChannel($busRoutingChannel);
                    $this->messageHandlerBuilders[$key] = $messageHandlerBuilder->withInputChannelName($handlerExecutionChannel);
                    $this->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel($handlerExecutionChannel));

                    $consequentialChannels = $asynchronousMessageChannels;
                    $consequentialChannels[] = $handlerExecutionChannel;
                    /**
                     * This provides endpoint that is called by gateway (bus).
                     * Then it outputs message to asynchronous message channel.
                     * Then when message is consumed it's routed by routing slip
                     * to target handler
                     */
                    $generatedEndpointId = Uuid::uuid4()->toString();
                    $this->registerMessageHandler(
                        UninterruptibleServiceActivator::create(
                            RoutingSlipPrepender::create(
                                $consequentialChannels,
                                [
                                    MessageBusChannel::COMMAND_CHANNEL_NAME_BY_NAME,
                                    MessageBusChannel::EVENT_CHANNEL_NAME_BY_NAME,
                                ]
                            ),
                            'transform',
                        )
                            ->withEndpointId($generatedEndpointId)
                            ->withInputChannelName($busRoutingChannel)
                            ->withOutputMessageChannel($asynchronousMessageChannel)
                    );

                    if (array_key_exists($messageHandlerBuilder->getEndpointId(), $this->pollingMetadata)) {
                        $this->pollingMetadata[$generatedEndpointId] = $this->pollingMetadata[$messageHandlerBuilder->getEndpointId()];
                        unset($this->pollingMetadata[$messageHandlerBuilder->getEndpointId()]);
                    }
                    $foundEndpoint = true;
                    break;
                }
            }

            if (! $foundEndpoint) {
                throw ConfigurationException::create("Registered asynchronous endpoint for not existing id {$targetEndpointId}");
            }
        }

        $asynchronousChannels = array_map(
            fn (MessageChannelBuilder $channel) => $channel->getMessageChannelName(),
            array_filter(
                $this->channelBuilders,
                fn (MessageChannelBuilder $channel) => $channel->isPollable()
                    && $channel->getMessageChannelName() !== NullableMessageChannel::CHANNEL_NAME
            )
        );

        foreach ($asynchronousChannels as $asynchronousChannel) {
            Assert::isTrue($this->channelBuilders[$asynchronousChannel]->isPollable(), "Asynchronous Message Channel {$asynchronousChannel} must be Pollable");
            //        needed for correct around intercepting, otherwise requestReply is outside of around interceptor scope
            /**
             * This is Bridge that will fetch the message and make use of routing_slip to target it
             * message handler.
             */
            $this->messageHandlerBuilders[$asynchronousChannel] = BridgeBuilder::create()
                ->withInputChannelName($asynchronousChannel)
                ->withEndpointId($asynchronousChannel);
        }

        $this->asynchronousEndpoints = [];
    }

    /**
     * @return void
     */
    private function configureDefaultMessageChannels(): void
    {
        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            if (! array_key_exists($messageHandlerBuilder->getInputMessageChannelName(), $this->channelBuilders)) {
                if (array_key_exists($messageHandlerBuilder->getInputMessageChannelName(), $this->defaultChannelBuilders)) {
                    $this->channelBuilders[$messageHandlerBuilder->getInputMessageChannelName()] = $this->defaultChannelBuilders[$messageHandlerBuilder->getInputMessageChannelName()];
                } else {
                    $this->channelBuilders[$messageHandlerBuilder->getInputMessageChannelName()] = SimpleMessageChannelBuilder::createDirectMessageChannel($messageHandlerBuilder->getInputMessageChannelName());
                }
            }
        }

        foreach ($this->defaultChannelBuilders as $name => $defaultChannelBuilder) {
            if (! array_key_exists($name, $this->channelBuilders)) {
                $this->channelBuilders[$name] = $defaultChannelBuilder;
            }
        }
    }

    private function hasMessageHandlerWithName(PollingMetadata $pollingMetadata): bool
    {
        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            if ($messageHandlerBuilder->getEndpointId() == $pollingMetadata->getEndpointId()) {
                return true;
            }
        }

        return false;
    }

    private function hasChannelAdapterWithName(PollingMetadata $pollingMetadata): bool
    {
        foreach ($this->channelAdapters as $channelAdapter) {
            if ($channelAdapter->getEndpointId() == $pollingMetadata->getEndpointId()) {
                return true;
            }
        }

        return false;
    }

    public static function prepareWithDefaultsForTesting(
        array $extensionObjects = [],
        ?ServiceConfiguration $serviceConfiguration = null
    ): Configuration {
        return self::prepare(
            __DIR__ .'/../../../',
            InMemoryConfigurationVariableService::createEmpty(),
            $serviceConfiguration ?? ServiceConfiguration::createWithAsynchronicityOnly(),
            $extensionObjects,
            true,
        );
    }

    public static function prepare(
        string $rootPathToSearchConfigurationFor,
        ConfigurationVariableService $configurationVariableService,
        ServiceConfiguration $serviceConfiguration,
        array $userLandClassesToRegister = [],
        bool $enableTestPackage = false,
    ): Configuration {
        $serviceConfiguration = self::addCorePackage($serviceConfiguration, $enableTestPackage);

        return self::prepareWithAnnotationFinder(
            AnnotationFinderFactory::createForAttributes(
                realpath($rootPathToSearchConfigurationFor),
                $serviceConfiguration->getNamespaces(),
                $serviceConfiguration->getEnvironment(),
                $serviceConfiguration->getLoadedCatalog() ?? '',
                self::getModuleClassesFor($serviceConfiguration),
                $userLandClassesToRegister,
                $enableTestPackage
            ),
            $configurationVariableService,
            $serviceConfiguration,
            $enableTestPackage,
        );
    }

    public static function prepareWithAnnotationFinder(
        AnnotationFinder $annotationFinder,
        ConfigurationVariableService $configurationVariableService,
        ServiceConfiguration $serviceConfiguration,
        bool $enableTestPackage = false,
    ): Configuration {
        $serviceConfiguration = self::addCorePackage($serviceConfiguration, $enableTestPackage);

        $preparationInterfaceRegistry = InterfaceToCallRegistry::createWith($annotationFinder);
        return self::prepareWithModuleRetrievingService(
            new AnnotationModuleRetrievingService(
                $annotationFinder,
                $preparationInterfaceRegistry,
                $configurationVariableService
            ),
            $preparationInterfaceRegistry,
            $serviceConfiguration,
        );
    }

    /**
     * @TODO that method should stay private, require refactoring tests
     */
    public static function prepareWithModuleRetrievingService(
        ModuleRetrievingService $moduleConfigurationRetrievingService,
        InterfaceToCallRegistry $preparationInterfaceRegistry,
        ServiceConfiguration $applicationConfiguration,
    ): MessagingSystemConfiguration {
        return new self(
            $moduleConfigurationRetrievingService,
            $moduleConfigurationRetrievingService->findAllExtensionObjects(),
            $preparationInterfaceRegistry,
            $applicationConfiguration,
        );
    }

    public function requireConsumer(string $endpointId): Configuration
    {
        $this->requiredConsumerEndpointIds[] = $endpointId;

        return $this;
    }

    public function requireReference(string $referenceId, string $errorMessage): Configuration
    {
        $this->requiredReferences[$referenceId] = $errorMessage;

        return $this;
    }

    public function withExternalContainer(?ContainerInterface $externalContainer): Configuration
    {
        $this->externalContainer = $externalContainer;

        return $this;
    }

    /**
     * @param PollingMetadata $pollingMetadata
     *
     * @return Configuration
     */
    public function registerPollingMetadata(PollingMetadata $pollingMetadata): Configuration
    {
        $this->pollingMetadata[$pollingMetadata->getEndpointId()] = $pollingMetadata;

        return $this;
    }

    public function registerBeforeSendInterceptor(MethodInterceptorBuilder $methodInterceptor): Configuration
    {
        $this->beforeSendInterceptors[] = $methodInterceptor;

        return $this;
    }

    /**
     * @param array<MethodInterceptorBuilder|AroundInterceptorBuilder> $methodInterceptors
     *
     * @return array
     */
    private function orderMethodInterceptors(array $methodInterceptors): array
    {
        usort(
            $methodInterceptors,
            function (MethodInterceptorBuilder|AroundInterceptorBuilder $methodInterceptor, MethodInterceptorBuilder|AroundInterceptorBuilder $toCompare) {
                if ($methodInterceptor->getPrecedence() === $toCompare->getPrecedence()) {
                    return 0;
                }

                if ($methodInterceptor->getPrecedence() > $toCompare->getPrecedence()) {
                    return 1;
                }

                return -1;
            }
        );

        return $methodInterceptors;
    }

    public function registerBeforeMethodInterceptor(MethodInterceptorBuilder $methodInterceptor): Configuration
    {
        $this->beforeCallMethodInterceptors[] = $methodInterceptor;

        return $this;
    }

    public function registerAfterMethodInterceptor(MethodInterceptorBuilder $methodInterceptor): Configuration
    {
        $this->afterCallMethodInterceptors[] = $methodInterceptor;

        return $this;
    }

    /**
     * @param AroundInterceptorBuilder $aroundInterceptorReference
     *
     * @return Configuration
     */
    public function registerAroundMethodInterceptor(AroundInterceptorBuilder $aroundInterceptorReference): Configuration
    {
        $this->aroundMethodInterceptors[] = $aroundInterceptorReference;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerAsynchronousEndpoint(array|string $asynchronousChannelNames, string $targetEndpointId): Configuration
    {
        $this->asynchronousEndpoints[$targetEndpointId] = is_string($asynchronousChannelNames) ? [$asynchronousChannelNames] : $asynchronousChannelNames;

        return $this;
    }

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     *
     * @return Configuration
     * @throws ConfigurationException
     * @throws Exception
     * @throws MessagingException
     */
    public function registerMessageHandler(MessageHandlerBuilder $messageHandlerBuilder): Configuration
    {
        Assert::notNullAndEmpty($messageHandlerBuilder->getInputMessageChannelName(), "Lack information about input message channel for {$messageHandlerBuilder}");
        if (is_null($messageHandlerBuilder->getEndpointId()) || $messageHandlerBuilder->getEndpointId() === '') {
            $messageHandlerBuilder->withEndpointId(Uuid::uuid4()->toString());
        }
        if (array_key_exists($messageHandlerBuilder->getEndpointId(), $this->messageHandlerBuilders)) {
            throw ConfigurationException::create("Trying to register endpoints with same id {$messageHandlerBuilder->getEndpointId()}. {$messageHandlerBuilder} and {$this->messageHandlerBuilders[$messageHandlerBuilder->getEndpointId()]}");
        }
        if ($messageHandlerBuilder->getInputMessageChannelName() === $messageHandlerBuilder->getEndpointId()) {
            throw ConfigurationException::create("Can't register message handler {$messageHandlerBuilder} with same endpointId as inputChannelName.");
        }

        $this->messageHandlerBuilders[$messageHandlerBuilder->getEndpointId()] = $messageHandlerBuilder;

        return $this;
    }

    /**
     * @throws MessagingException
     */
    private function verifyEndpointAndChannelNameUniqueness(): void
    {
        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            foreach ($this->channelBuilders as $channelBuilder) {
                if ($messageHandlerBuilder->getEndpointId() === $channelBuilder->getMessageChannelName()) {
                    throw ConfigurationException::create("Endpoint id should not be the same as existing channel name. Got {$messageHandlerBuilder} which use endpoint id same as existing channel name {$channelBuilder->getMessageChannelName()}");
                }
            }
            foreach ($this->defaultChannelBuilders as $channelBuilder) {
                if ($messageHandlerBuilder->getEndpointId() === $channelBuilder->getMessageChannelName()) {
                    throw ConfigurationException::create("Endpoint id should not be the same as existing channel name. Got {$messageHandlerBuilder} which use endpoint id same as existing channel name {$channelBuilder->getMessageChannelName()}");
                }
            }
        }
    }

    /**
     * @param MessageChannelBuilder $messageChannelBuilder
     *
     * @return Configuration
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function registerMessageChannel(MessageChannelBuilder $messageChannelBuilder): Configuration
    {
        Assert::notNullAndEmpty($messageChannelBuilder->getMessageChannelName(), "Message channel name can't be empty");
        if (array_key_exists($messageChannelBuilder->getMessageChannelName(), $this->channelBuilders)) {
            throw ConfigurationException::create("Trying to register message channel with name `{$messageChannelBuilder->getMessageChannelName()}` twice.");
        }

        $this->channelBuilders[$messageChannelBuilder->getMessageChannelName()] = $messageChannelBuilder;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerDefaultChannelFor(MessageChannelBuilder $messageChannelBuilder): Configuration
    {
        $this->defaultChannelBuilders[$messageChannelBuilder->getMessageChannelName()] = $messageChannelBuilder;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerConsumer(ChannelAdapterConsumerBuilder $consumerBuilder): Configuration
    {
        if (array_key_exists($consumerBuilder->getEndpointId(), $this->channelAdapters)) {
            throw ConfigurationException::create("Trying to register consumers under same endpoint id {$consumerBuilder->getEndpointId()}. Change the name of one of them.");
        }

        $this->channelAdapters[$consumerBuilder->getEndpointId()] = $consumerBuilder;

        return $this;
    }

    /**
     * @param GatewayProxyBuilder $gatewayBuilder
     *
     * @return Configuration
     */
    public function registerGatewayBuilder(GatewayProxyBuilder $gatewayBuilder): Configuration
    {
        foreach ($this->gatewayBuilders as $registeredGatewayBuilder) {
            if (
                $registeredGatewayBuilder->getReferenceName() === $gatewayBuilder->getReferenceName()
                && $registeredGatewayBuilder->getRelatedMethodName() === $gatewayBuilder->getRelatedMethodName()
            ) {
                throw ConfigurationException::create(sprintf('Registering Gateway for the same class and method twice: %s::%s', $gatewayBuilder->getReferenceName(), $gatewayBuilder->getRelatedMethodName()));
            }
        }

        $this->gatewayBuilders[] = $gatewayBuilder;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerConsumerFactory(MessageHandlerConsumerBuilder $consumerFactory): Configuration
    {
        $this->consumerFactories[] = $consumerFactory;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRegisteredGateways(): array
    {
        return $this->gatewayBuilders;
    }

    /**
     * @inheritDoc
     */
    public function registerConverter(CompilableBuilder $converterBuilder): Configuration
    {
        $this->converterBuilders[] = $converterBuilder;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerMessageConverter(string $referenceName): Configuration
    {
        $this->messageConverterReferenceNames[] = $referenceName;

        return $this;
    }

    public function registerServiceDefinition(string|Reference $id, Definition|Reference|array $definition = []): Configuration
    {
        if (! isset($this->serviceDefinitions[(string) $id])) {
            if (is_array($definition)) {
                $definition = new Definition((string) $id, $definition);
            }
            $this->serviceDefinitions[(string) $id] = $definition;
        }
        return $this;
    }

    public function isRunningForEnterpriseLicence(): bool
    {
        return $this->isRunningForEnterpriseLicence;
    }

    public function isRunningForTest(): bool
    {
        return $this->isRunningForTest;
    }

    public function addCompilerPass(CompilerPass $compilerPass): self
    {
        $this->compilerPasses[] = $compilerPass;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $builder): void
    {
        $this->moduleConfigurationCompilerPass->process($builder);
        $this->prepareAndOptimizeConfiguration($this->interfaceToCallRegistry);

        $messagingBuilder = new MessagingContainerBuilder(
            $builder,
            $this->interfaceToCallRegistry,
            $this->applicationConfiguration,
            $this->pollingMetadata,
            $this->beforeCallMethodInterceptors,
            $this->aroundMethodInterceptors,
            $this->afterCallMethodInterceptors,
        );

        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            if (! isset($this->channelBuilders[$gatewayBuilder->getRequestChannelName()])) {
                throw ConfigurationException::create("Gateway {$gatewayBuilder->getInterfaceName()}:{$gatewayBuilder->getRelatedMethodName()} has not existing request channel {$gatewayBuilder->getRequestChannelName()}. Have you forgot to declare some Message Handler for it?");
            }
        }
        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            if ($messageHandlerBuilder instanceof MessageHandlerBuilderWithOutputChannel) {
                if (! $this->isChannelDefinedFor($messageHandlerBuilder)) {
                    throw ConfigurationException::create("Message handler {$messageHandlerBuilder} has not existing output channel {$messageHandlerBuilder->getInputMessageChannelName()}. Have you forgot to declare some Message Handler for it?");
                }
            }
        }

        foreach ($this->serviceDefinitions as $id => $definition) {
            $messagingBuilder->register($id, $definition);
        }

        $messagingBuilder->register(
            LoggingGateway::class,
            (new Definition(LoggingService::class))
                ->addMethodCall('registerLogger', [new Reference('logger', ContainerImplementation::NULL_ON_INVALID_REFERENCE)])
        );

        // TODO: some service configuration should be handled at runtime. Here they are all cached in the container
        //        $messagingBuilder->register('config.defaultSerializationMediaType', MediaType::parseMediaType($this->applicationConfiguration->getDefaultSerializationMediaType()));

        $converters = [];
        foreach ($this->converterBuilders as $converterBuilder) {
            $converters[] = $converterBuilder->compile($messagingBuilder);
        }
        $messagingBuilder->register(ConversionService::REFERENCE_NAME, new Definition(AutoCollectionConversionService::class, ['converters' => $converters]));

        $channelInterceptorsByImportance = $this->channelInterceptorBuilders;
        $channelInterceptorsByChannelName = [];
        foreach ($channelInterceptorsByImportance as $channelInterceptors) {
            /** @var ChannelInterceptorBuilder $channelInterceptor */
            foreach ($channelInterceptors as $channelInterceptor) {
                $channelInterceptorsByChannelName[$channelInterceptor->relatedChannelName()][] = $channelInterceptor;
            }
        }

        foreach ($this->channelBuilders as $channelsBuilder) {
            $channelReference = new ChannelReference($channelsBuilder->getMessageChannelName());
            $channelDefinition = $channelsBuilder->compile($messagingBuilder);
            $interceptorsForChannel = [];
            foreach ($channelInterceptorsByChannelName as $channelName => $interceptors) {
                $regexChannel = str_replace('*', '.*', $channelName);
                $regexChannel = str_replace('\\', '\\\\', $regexChannel);
                if (preg_match("#^{$regexChannel}$#", $channelsBuilder->getMessageChannelName())) {
                    foreach ($interceptors as $interceptor) {
                        $interceptorsForChannel[] = $interceptor->compile($messagingBuilder);
                    }
                }
            }
            if ($interceptorsForChannel) {
                $isPollable = is_a($channelDefinition->getClassName(), PollableChannel::class, true);
                $channelDefinition = new Definition($isPollable ? PollableChannelInterceptorAdapter::class : EventDrivenChannelInterceptorAdapter::class, [
                    $channelDefinition,
                    $interceptorsForChannel,
                ]);
            }
            $messagingBuilder->register($channelReference, $channelDefinition);
        }

        foreach ($this->moduleReferenceSearchService->getAllRegisteredReferences() as $id => $object) {
            if (! $object instanceof CompilableBuilder) {
                throw ConfigurationException::create("Reference {$id} is not compilable");
            }
            $messagingBuilder->register($id, $object->compile($messagingBuilder));
        }

        foreach ($this->channelAdapters as $channelAdapter) {
            $channelAdapter->registerConsumer($messagingBuilder);
        }

        foreach ($this->getMessageHandlersBasedOnPriority() as $messageHandlerBuilder) {
            $inputChannelBuilder = $this->channelBuilders[$messageHandlerBuilder->getInputMessageChannelName()] ?? throw ConfigurationException::create("Missing channel with name {$messageHandlerBuilder->getInputMessageChannelName()} for {$messageHandlerBuilder}");
            foreach ($this->consumerFactories as $consumerFactory) {
                if ($consumerFactory->isSupporting($messageHandlerBuilder, $inputChannelBuilder)) {
                    $consumerFactory->registerConsumer($messagingBuilder, $messageHandlerBuilder);
                    break;
                }
            }
        }
        $gatewayList = [];
        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            $gatewayBuilder->registerProxy($messagingBuilder);
            $gatewayList[$gatewayBuilder->getReferenceName()] = $gatewayBuilder->getInterfaceName();
        }
        $gatewayListReferences = [];
        foreach ($gatewayList as $referenceName => $interfaceName) {
            $gatewayListReferences[] = new Definition(GatewayProxyReference::class, [$referenceName, $interfaceName]);
        }

        foreach ($this->consoleCommands as $consoleCommandConfiguration) {
            $builder->register("console.{$consoleCommandConfiguration->getName()}", new Definition(ConsoleCommandRunner::class, [
                Reference::to(MessagingEntrypointWithHeadersPropagation::class),
                $consoleCommandConfiguration,
            ]));
        }

        $messagingBuilder->register(ConfiguredMessagingSystem::class, new Definition(MessagingSystemContainer::class, [new Reference(ContainerInterface::class), $messagingBuilder->getPollingEndpoints(), $gatewayListReferences]));
        (new RegisterSingletonMessagingServices())->process($builder);
        foreach ($this->compilerPasses as $compilerPass) {
            $compilerPass->process($builder);
        }

        (new Container\Compiler\ValidateRequiredReferencesPass(
            $this->requiredReferences,
            $this->applicationConfiguration->isModulePackageEnabled(ModulePackageList::TEST_PACKAGE),
            $this->externalContainer
        ))->process($builder);
    }

    /**
     * @return array<string, string>
     */
    public function getRequiredReferencesForValidation(): array
    {
        if ($this->applicationConfiguration->isModulePackageEnabled(ModulePackageList::TEST_PACKAGE)) {
            return [];
        }

        return $this->requiredReferences;
    }

    /**
     * @deprecated
     */
    public function buildMessagingSystemFromConfiguration(?ContainerInterface $referenceSearchService = null): ConfiguredMessagingSystem
    {
        return ContainerConfig::buildMessagingSystemInMemoryContainer($this, $referenceSearchService);
    }

    public static function prepareCacheDirectory(ServiceCacheConfiguration $serviceCacheConfiguration): void
    {
        if (! $serviceCacheConfiguration->shouldUseCache()) {
            return;
        }

        $cacheDirectoryPath = $serviceCacheConfiguration->getPath();
        if (! is_dir($cacheDirectoryPath)) {
            $mkdirResult = @mkdir($cacheDirectoryPath, 0775, true);
            Assert::isTrue(
                $mkdirResult,
                "Not enough permissions to create cache directory {$cacheDirectoryPath}"
            );
        }
    }

    public function getRegisteredConsoleCommands(): array
    {
        return $this->consoleCommands;
    }

    public function registerConsoleCommand(ConsoleCommandConfiguration $consoleCommandConfiguration): Configuration
    {
        $this->consoleCommands[] = $consoleCommandConfiguration;

        return $this;
    }

    /**
     * @return MessageHandlerBuilder[]
     */
    private function getMessageHandlersBasedOnPriority(): array
    {
        $messageHandlerBuildersAccordinglyToPriority = [];
        $projectionHandlers = [];
        $aggregateHandlers = [];
        $otherHandlers = [];
        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            $priority = PriorityBasedOnType::default();
            if ($messageHandlerBuilder instanceof MessageHandlerBuilderWithOutputChannel) {
                $interfaceToCall = $messageHandlerBuilder->getInterceptedInterface($this->interfaceToCallRegistry);
                if ($interfaceToCall->hasAnnotation(PriorityBasedOnType::class)) {
                    $priority = $interfaceToCall->getAnnotationsByImportanceOrder(PriorityBasedOnType::class)[0];
                }
            }
            if ($messageHandlerBuilder instanceof InterceptedEndpoint) {
                foreach ($messageHandlerBuilder->getEndpointAnnotations() as $endpointAnnotation) {
                    $endpointAnnotation = $endpointAnnotation->instance();
                    if ($endpointAnnotation instanceof PriorityBasedOnType) {
                        $priority = $endpointAnnotation;
                    }
                }
            }

            if ($priority->hasPriority(PriorityBasedOnType::PROJECTION_TYPE)) {
                $projectionHandlers[$priority->getNumber()][] = $messageHandlerBuilder;
            } elseif ($priority->hasPriority(PriorityBasedOnType::AGGREGATE_TYPE)) {
                $aggregateHandlers[$priority->getNumber()][] = $messageHandlerBuilder;
            } else {
                $otherHandlers[$priority->getNumber()][] = $messageHandlerBuilder;
            }
        }

        $minimumPriority = min(array_merge(array_keys($projectionHandlers), array_keys($aggregateHandlers), array_keys($otherHandlers), [1]));
        $maximumPriority = max(array_merge(array_keys($projectionHandlers), array_keys($aggregateHandlers), array_keys($otherHandlers), [1]));

        for ($priority = $maximumPriority; $priority >= $minimumPriority; $priority--) {
            if (isset($projectionHandlers[$priority])) {
                foreach ($projectionHandlers[$priority] as $messageHandlerBuilder) {
                    $messageHandlerBuildersAccordinglyToPriority[] = $messageHandlerBuilder;
                }
            }
            if (isset($aggregateHandlers[$priority])) {
                foreach ($aggregateHandlers[$priority] as $messageHandlerBuilder) {
                    $messageHandlerBuildersAccordinglyToPriority[] = $messageHandlerBuilder;
                }
            }
            if (isset($otherHandlers[$priority])) {
                foreach ($otherHandlers[$priority] as $messageHandlerBuilder) {
                    $messageHandlerBuildersAccordinglyToPriority[] = $messageHandlerBuilder;
                }
            }
        }

        return $messageHandlerBuildersAccordinglyToPriority;
    }

    public function isChannelDefinedFor(MessageHandlerBuilderWithOutputChannel|MessageHandlerBuilder $messageHandlerBuilder): bool
    {
        if (! $messageHandlerBuilder->getOutputMessageChannelName()) {
            return true;
        }

        if (isset($this->channelBuilders[$messageHandlerBuilder->getOutputMessageChannelName()])) {
            return true;
        }

        foreach ($this->messageHandlerBuilders as $comparableHandler) {
            if (
                $comparableHandler->getInputMessageChannelName() === $messageHandlerBuilder->getOutputMessageChannelName()
                && $comparableHandler->getEndpointId() !== $messageHandlerBuilder->getEndpointId()
            ) {
                return true;
            }
        }

        return false;
    }
}
