<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use function array_map;

use Ecotone\AnnotationFinder\AnnotationFinder;

use Ecotone\AnnotationFinder\AnnotationFinderFactory;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\EventDrivenChannelInterceptorAdapter;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\PollableChannelInterceptorAdapter;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModuleRetrievingService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\AsynchronousModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptor\BeforeSendChannelInterceptorBuilder;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\ChannelReference;
use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Compiler\RegisterSingletonMessagingServices;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\ContainerConfig;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\GatewayProxyReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\PollingMetadataReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\ConfigurationVariableService;
use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use Ecotone\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Gateway\MessagingEntrypointWithHeadersPropagation;
use Ecotone\Messaging\Handler\Bridge\BridgeBuilder;
use Ecotone\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\InterceptorWithPointCut;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\UninterruptibleServiceActivator;
use Ecotone\Messaging\Handler\Transformer\HeaderEnricher;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Config\BusModule;
use Exception;

use function is_a;

use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;

/**
 * Class Configuration
 * @package Ecotone\Messaging\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
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
     * @var array<string, string>
     */
    private array $messageHandlerBuilderToChannel = [];
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
     * @var MethodInterceptor[]
     */
    private array $beforeSendInterceptors = [];
    /**
     * @var MethodInterceptor[]
     */
    private array $beforeCallMethodInterceptors = [];
    /**
     * @var AroundInterceptorBuilder[]
     */
    private array $aroundMethodInterceptors = [];
    /**
     * @var MethodInterceptor[]
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
    /**
     * @var string[]
     */
    private array $gatewayClassesToGenerateProxies = [];
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
     * @var array<string, Definition> $serviceDefinitions
     */
    private array $serviceDefinitions = [];

    /**
     * @var array<string, Reference> $serviceAliases
     */
    private array $serviceAliases = [];

    private InterfaceToCallRegistry $interfaceToCallRegistry;

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
        $extensionObjects[] = $serviceConfiguration;
        $this->initialize($moduleConfigurationRetrievingService, $extensionObjects, $serviceConfiguration);
    }

    /**
     * @param string[] $skippedModulesPackages
     */
    private function initialize(ModuleRetrievingService $moduleConfigurationRetrievingService, array $serviceExtensions, ServiceConfiguration $applicationConfiguration): void
    {
        $moduleReferenceSearchService = ModuleReferenceSearchService::createEmpty();

        $modules = $moduleConfigurationRetrievingService->findAllModuleConfigurations($applicationConfiguration->getSkippedModulesPackages());
        $moduleExtensions = [];

        $extensionObjects = $serviceExtensions;
        foreach ($modules as $module) {
            $extensionObjects = array_merge($extensionObjects, $module->getModuleExtensions($serviceExtensions));
        }
        foreach ($modules as $module) {
            $moduleExtensions[get_class($module)] = [];
            foreach ($extensionObjects as $extensionObject) {
                if ($module->canHandle($extensionObject)) {
                    $moduleExtensions[get_class($module)][] = $extensionObject;
                }
            }
        }

        foreach ($modules as $module) {
            $module->prepare(
                $this,
                $moduleExtensions[get_class($module)],
                $moduleReferenceSearchService,
                $this->interfaceToCallRegistry,
            );
        }

        $this->gatewayClassesToGenerateProxies = [];

        $this->moduleReferenceSearchService = $moduleReferenceSearchService;
    }

    private function prepareAndOptimizeConfiguration(InterfaceToCallRegistry $interfaceToCallRegistry, ServiceConfiguration $applicationConfiguration): void
    {
        foreach ($this->channelAdapters as $channelAdapter) {
            $channelAdapter->withEndpointAnnotations(array_merge($channelAdapter->getEndpointAnnotations(), [new AttributeDefinition(AsynchronousRunningEndpoint::class, [$channelAdapter->getEndpointId()])]));
        }

        /** @var BeforeSendChannelInterceptorBuilder[] $beforeSendInterceptors */
        $beforeSendInterceptors = [];
        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            if ($messageHandlerBuilder instanceof MessageHandlerBuilderWithOutputChannel) {
                if ($this->beforeSendInterceptors) {
                    $interceptorWithPointCuts = $this->getRelatedInterceptors($this->beforeSendInterceptors, $messageHandlerBuilder->getInterceptedInterface($interfaceToCallRegistry), $messageHandlerBuilder->getEndpointAnnotations(), $messageHandlerBuilder->getRequiredInterceptorNames(), $interfaceToCallRegistry);
                    foreach ($interceptorWithPointCuts as $interceptorReference) {
                        $beforeSendInterceptors[] = new BeforeSendChannelInterceptorBuilder($messageHandlerBuilder->getInputMessageChannelName(), $interceptorReference);
                    }
                }
            }
        }

        $beforeSendInterceptors = array_unique($beforeSendInterceptors);
        foreach ($beforeSendInterceptors as $beforeSendInterceptor) {
            $this->registerChannelInterceptor($beforeSendInterceptor);
        }

        $this->configureDefaultMessageChannels();
        $this->configureAsynchronousEndpoints();
        $this->configureInterceptors($interfaceToCallRegistry);

        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            $this->addDefaultPollingConfiguration($messageHandlerBuilder->getEndpointId());
        }
        foreach ($this->channelAdapters as $channelAdapter) {
            $this->addDefaultPollingConfiguration($channelAdapter->getEndpointId());
        }

        foreach ($this->requiredConsumerEndpointIds as $requiredConsumerEndpointId) {
            if (! array_key_exists($requiredConsumerEndpointId, $this->messageHandlerBuilders) && ! array_key_exists($requiredConsumerEndpointId, $this->channelAdapters)) {
                throw ConfigurationException::create("Consumer with id {$requiredConsumerEndpointId} has no configuration defined. Define consumer configuration and retry.");
            }
        }
        foreach ($this->pollingMetadata as $pollingMetadata) {
            if (! $this->hasMessageHandlerWithName($pollingMetadata) && ! $this->hasChannelAdapterWithName($pollingMetadata)) {
                throw ConfigurationException::create("Trying to register polling meta data for non existing endpoint {$pollingMetadata->getEndpointId()}. Verify if there is any asynchronous endpoint with such name.");
            }
        }

        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            $gatewayBuilder->withMessageConverters($this->messageConverterReferenceNames);
        }
    }

    /**
     * @param InterceptorWithPointCut[] $interceptors
     * @param InterfaceToCall $interceptedInterface
     * @param AttributeDefinition[] $endpointAnnotations
     * @param string[] $requiredInterceptorNames
     *
     * @return InterceptorWithPointCut[]|AroundInterceptorBuilder[]|MessageHandlerBuilderWithOutputChannel[]
     * @throws MessagingException
     */
    private function getRelatedInterceptors(array $interceptors, InterfaceToCall $interceptedInterface, iterable $endpointAnnotations, iterable $requiredInterceptorNames, InterfaceToCallRegistry $interfaceToCallRegistry): iterable
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

            if ($interceptor->doesItCutWith($interceptedInterface, $endpointAnnotationsInstances, $interfaceToCallRegistry)) {
                $relatedInterceptors[] = $interceptor->addInterceptedInterfaceToCall($interceptedInterface, $endpointAnnotations);
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
        krsort($this->channelInterceptorBuilders);

        return $this;
    }

    /**
     * @return void
     */
    private function configureAsynchronousEndpoints(): void
    {
        $allAsynchronousChannels = [];
        foreach ($this->asynchronousEndpoints as $targetEndpointId => $asynchronousMessageChannels) {
            $allAsynchronousChannels = array_merge($allAsynchronousChannels, $asynchronousMessageChannels);
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
                            HeaderEnricher::create([
                                BusModule::COMMAND_CHANNEL_NAME_BY_NAME => null,
                                BusModule::COMMAND_CHANNEL_NAME_BY_OBJECT => null,
                                BusModule::EVENT_CHANNEL_NAME_BY_OBJECT => null,
                                BusModule::EVENT_CHANNEL_NAME_BY_NAME => null,
                                MessageHeaders::ROUTING_SLIP => implode(',', $consequentialChannels),
                            ]),
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

        foreach (array_unique($allAsynchronousChannels) as $asynchronousChannel) {
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

    /**
     * @param InterfaceToCallRegistry $interfaceRegistry
     *
     * @return void
     * @throws MessagingException
     */
    private function configureInterceptors(InterfaceToCallRegistry $interfaceRegistry): void
    {
        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            if ($messageHandlerBuilder instanceof MessageHandlerBuilderWithOutputChannel) {
                $aroundInterceptors = [];
                $beforeCallInterceptors = [];
                $afterCallInterceptors = [];

                if ($this->beforeCallMethodInterceptors) {
                    $beforeCallInterceptors = $this->getRelatedInterceptors($this->beforeCallMethodInterceptors, $messageHandlerBuilder->getInterceptedInterface($interfaceRegistry), $messageHandlerBuilder->getEndpointAnnotations(), $messageHandlerBuilder->getRequiredInterceptorNames(), $interfaceRegistry);
                }
                if ($this->aroundMethodInterceptors) {
                    $aroundInterceptors = $this->getRelatedInterceptors($this->aroundMethodInterceptors, $messageHandlerBuilder->getInterceptedInterface($interfaceRegistry), $messageHandlerBuilder->getEndpointAnnotations(), $messageHandlerBuilder->getRequiredInterceptorNames(), $interfaceRegistry);
                }
                if ($this->afterCallMethodInterceptors) {
                    $afterCallInterceptors = $this->getRelatedInterceptors($this->afterCallMethodInterceptors, $messageHandlerBuilder->getInterceptedInterface($interfaceRegistry), $messageHandlerBuilder->getEndpointAnnotations(), $messageHandlerBuilder->getRequiredInterceptorNames(), $interfaceRegistry);
                }

                foreach ($aroundInterceptors as $aroundInterceptorReference) {
                    $messageHandlerBuilder = $messageHandlerBuilder->addAroundInterceptor($aroundInterceptorReference);
                    $this->messageHandlerBuilders[$messageHandlerBuilder->getEndpointId()] = $messageHandlerBuilder;
                }
                if ($beforeCallInterceptors || $afterCallInterceptors) {
                    $outputChannel = $messageHandlerBuilder->getOutputMessageChannelName();
                    $messageHandlerBuilder = $messageHandlerBuilder
                        ->withOutputMessageChannel('');
                    $messageHandlerBuilderToUse = ChainMessageHandlerBuilder::create()
                        ->withEndpointId($messageHandlerBuilder->getEndpointId())
                        ->withInputChannelName($messageHandlerBuilder->getInputMessageChannelName())
                        ->withOutputMessageChannel($outputChannel);

                    foreach ($beforeCallInterceptors as $beforeCallInterceptor) {
                        $messageHandlerBuilderToUse = $messageHandlerBuilderToUse->chain($beforeCallInterceptor->getInterceptingObject());
                    }
                    $messageHandlerBuilderToUse = $messageHandlerBuilderToUse->chain($messageHandlerBuilder);
                    foreach ($afterCallInterceptors as $afterCallInterceptor) {
                        $messageHandlerBuilderToUse = $messageHandlerBuilderToUse->chain($afterCallInterceptor->getInterceptingObject());
                    }

                    $this->messageHandlerBuilders[$messageHandlerBuilder->getEndpointId()] = $messageHandlerBuilderToUse;
                }
            }
        }

        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            $aroundInterceptors = [];
            $beforeCallInterceptors = [];
            $afterCallInterceptors = [];
            if ($this->beforeCallMethodInterceptors) {
                $beforeCallInterceptors = $this->getRelatedInterceptors($this->beforeCallMethodInterceptors, $gatewayBuilder->getInterceptedInterface($interfaceRegistry), $gatewayBuilder->getEndpointAnnotations(), $gatewayBuilder->getRequiredInterceptorNames(), $interfaceRegistry);
            }
            if ($this->aroundMethodInterceptors) {
                $aroundInterceptors = $this->getRelatedInterceptors($this->aroundMethodInterceptors, $gatewayBuilder->getInterceptedInterface($interfaceRegistry), $gatewayBuilder->getEndpointAnnotations(), $gatewayBuilder->getRequiredInterceptorNames(), $interfaceRegistry);
            }
            if ($this->afterCallMethodInterceptors) {
                $afterCallInterceptors = $this->getRelatedInterceptors($this->afterCallMethodInterceptors, $gatewayBuilder->getInterceptedInterface($interfaceRegistry), $gatewayBuilder->getEndpointAnnotations(), $gatewayBuilder->getRequiredInterceptorNames(), $interfaceRegistry);
            }

            foreach ($aroundInterceptors as $aroundInterceptor) {
                $gatewayBuilder->addAroundInterceptor($aroundInterceptor);
            }
            foreach ($beforeCallInterceptors as $beforeCallInterceptor) {
                $gatewayBuilder->addBeforeInterceptor($beforeCallInterceptor);
            }
            foreach ($afterCallInterceptors as $afterCallInterceptor) {
                $gatewayBuilder->addAfterInterceptor($afterCallInterceptor);
            }
        }

        foreach ($this->channelAdapters as $channelAdapter) {
            $aroundInterceptors = [];
            $beforeCallInterceptors = [];
            $afterCallInterceptors = [];
            if ($this->beforeCallMethodInterceptors) {
                $beforeCallInterceptors = $this->getRelatedInterceptors($this->beforeCallMethodInterceptors, $channelAdapter->getInterceptedInterface($interfaceRegistry), $channelAdapter->getEndpointAnnotations(), $channelAdapter->getRequiredInterceptorNames(), $interfaceRegistry);
            }
            if ($this->aroundMethodInterceptors) {
                $aroundInterceptors = $this->getRelatedInterceptors($this->aroundMethodInterceptors, $channelAdapter->getInterceptedInterface($interfaceRegistry), $channelAdapter->getEndpointAnnotations(), $channelAdapter->getRequiredInterceptorNames(), $interfaceRegistry);
            }
            if ($this->afterCallMethodInterceptors) {
                $afterCallInterceptors = $this->getRelatedInterceptors($this->afterCallMethodInterceptors, $channelAdapter->getInterceptedInterface($interfaceRegistry), $channelAdapter->getEndpointAnnotations(), $channelAdapter->getRequiredInterceptorNames(), $interfaceRegistry);
            }

            foreach ($aroundInterceptors as $aroundInterceptor) {
                $channelAdapter->addAroundInterceptor($aroundInterceptor);
            }
            foreach ($beforeCallInterceptors as $beforeCallInterceptor) {
                $channelAdapter->addBeforeInterceptor($beforeCallInterceptor);
            }
            foreach ($afterCallInterceptors as $afterCallInterceptor) {
                $channelAdapter->addAfterInterceptor($afterCallInterceptor);
            }
        }

        foreach ($this->consumerFactories as $consumerFactory) {
            if (! ($consumerFactory instanceof PollingConsumerBuilder)) {
                continue;
            }

            /** Name will be provided during build for given Message Handler. Looking in PollingConsumerBuilder. This is only for pointcut lookup */
            $endpointAnnotations = [new AttributeDefinition(AsynchronousRunningEndpoint::class, [''])];
            if ($this->aroundMethodInterceptors) {
                $aroundInterceptors = $this->getRelatedInterceptors(
                    $this->aroundMethodInterceptors,
                    $consumerFactory->getInterceptedInterface($interfaceRegistry),
                    $endpointAnnotations,
                    $consumerFactory->getRequiredInterceptorNames(),
                    $interfaceRegistry
                );

                foreach ($aroundInterceptors as $aroundInterceptor) {
                    $consumerFactory->addAroundInterceptor($aroundInterceptor);
                }
            }

            if ($this->beforeCallMethodInterceptors) {
                $beforeCallInterceptors = $this->getRelatedInterceptors($this->beforeCallMethodInterceptors, $consumerFactory->getInterceptedInterface($interfaceRegistry), $endpointAnnotations, $consumerFactory->getRequiredInterceptorNames(), $interfaceRegistry);
                foreach ($beforeCallInterceptors as $beforeCallInterceptor) {
                    $consumerFactory->addBeforeInterceptor($beforeCallInterceptor);
                }
            }
            if ($this->afterCallMethodInterceptors) {
                $afterCallInterceptors = $this->getRelatedInterceptors($this->afterCallMethodInterceptors, $consumerFactory->getInterceptedInterface($interfaceRegistry), $endpointAnnotations, $consumerFactory->getRequiredInterceptorNames(), $interfaceRegistry);
                foreach ($afterCallInterceptors as $afterCallInterceptor) {
                    $consumerFactory->addAfterInterceptor($afterCallInterceptor);
                }
            }
        }

        $this->beforeCallMethodInterceptors = [];
        $this->aroundMethodInterceptors = [];
        $this->afterCallMethodInterceptors = [];
    }

    private function addDefaultPollingConfiguration($endpointId): void
    {
        $pollingMetadata = PollingMetadata::create((string)$endpointId);
        if (array_key_exists($endpointId, $this->pollingMetadata)) {
            $pollingMetadata = $this->pollingMetadata[$endpointId];
        }

        if ($this->applicationConfiguration->getDefaultErrorChannel() && $pollingMetadata->isErrorChannelEnabled() && ! $pollingMetadata->getErrorChannelName()) {
            $pollingMetadata = $pollingMetadata
                ->setErrorChannelName($this->applicationConfiguration->getDefaultErrorChannel());
        }
        if ($this->applicationConfiguration->getDefaultMemoryLimitInMegabytes() && ! $pollingMetadata->getMemoryLimitInMegabytes()) {
            $pollingMetadata = $pollingMetadata
                ->setMemoryLimitInMegaBytes($this->applicationConfiguration->getDefaultMemoryLimitInMegabytes());
        }
        if ($this->applicationConfiguration->getConnectionRetryTemplate() && ! $pollingMetadata->getConnectionRetryTemplate()) {
            $pollingMetadata = $pollingMetadata
                ->setConnectionRetryTemplate($this->applicationConfiguration->getConnectionRetryTemplate());
        }

        $this->pollingMetadata[$endpointId] = $pollingMetadata;
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

    public static function prepareWithDefaults(ModuleRetrievingService $moduleConfigurationRetrievingService, ?ServiceConfiguration $serviceConfiguration = null): MessagingSystemConfiguration
    {
        return new self($moduleConfigurationRetrievingService, $moduleConfigurationRetrievingService->findAllExtensionObjects(), InterfaceToCallRegistry::createEmpty(), $serviceConfiguration ?? ServiceConfiguration::createWithDefaults());
    }

    public static function prepare(
        string $rootPathToSearchConfigurationFor,
        ConfigurationVariableService $configurationVariableService,
        ServiceConfiguration $serviceConfiguration,
        array $userLandClassesToRegister = [],
        bool $enableTestPackage = false
    ): Configuration {
        $requiredModules = [ModulePackageList::CORE_PACKAGE];
        if ($enableTestPackage) {
            $requiredModules[] = ModulePackageList::TEST_PACKAGE;
        }

        $serviceConfiguration = $serviceConfiguration->withSkippedModulePackageNames(array_diff($serviceConfiguration->getSkippedModulesPackages(), $requiredModules));

        $modulesClasses = [];
        foreach (array_diff(array_merge(ModulePackageList::allPackages(), [ModulePackageList::TEST_PACKAGE]), $serviceConfiguration->getSkippedModulesPackages()) as $availablePackage) {
            $modulesClasses = array_merge($modulesClasses, ModulePackageList::getModuleClassesForPackage($availablePackage));
        }

        return self::prepareWithAnnotationFinder(
            AnnotationFinderFactory::createForAttributes(
                realpath($rootPathToSearchConfigurationFor),
                $serviceConfiguration->getNamespaces(),
                $serviceConfiguration->getEnvironment(),
                $serviceConfiguration->getLoadedCatalog() ?? '',
                array_filter($modulesClasses, fn (string $moduleClassName): bool => class_exists($moduleClassName) || interface_exists($moduleClassName)),
                $userLandClassesToRegister,
                $enableTestPackage
            ),
            $configurationVariableService,
            $serviceConfiguration,
        );
    }

    private static function prepareWithAnnotationFinder(
        AnnotationFinder $annotationFinder,
        ConfigurationVariableService $configurationVariableService,
        ServiceConfiguration $serviceConfiguration,
    ): Configuration {
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

    /**
     * @param MethodInterceptor $methodInterceptor
     *
     * @return Configuration
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function registerBeforeSendInterceptor(MethodInterceptor $methodInterceptor): Configuration
    {
        $this->checkIfInterceptorIsCorrect($methodInterceptor);

        $interceptingObject = $methodInterceptor->getInterceptingObject();
        if ($interceptingObject instanceof ServiceActivatorBuilder) {
            $interceptingObject->withPassThroughMessageOnVoidInterface(true);
        }

        $this->beforeSendInterceptors[] = $methodInterceptor;
        $this->beforeSendInterceptors = $this->orderMethodInterceptors($this->beforeSendInterceptors);

        return $this;
    }

    /**
     * @param MethodInterceptor $methodInterceptor
     *
     * @throws ConfigurationException
     * @throws MessagingException
     */
    private function checkIfInterceptorIsCorrect(MethodInterceptor $methodInterceptor): void
    {
        if ($methodInterceptor->getMessageHandler()->getEndpointId()) {
            throw ConfigurationException::create("Interceptor {$methodInterceptor} should not contain EndpointId");
        }
        if ($methodInterceptor->getMessageHandler()->getInputMessageChannelName()) {
            throw ConfigurationException::create("Interceptor {$methodInterceptor} should not contain input channel. Interceptor is wired by endpoint id");
        }
        if ($methodInterceptor->getMessageHandler()->getOutputMessageChannelName()) {
            throw ConfigurationException::create("Interceptor {$methodInterceptor} should not contain output channel. Interceptor is wired by endpoint id");
        }
    }

    /**
     * @param MethodInterceptor[] $methodInterceptors
     *
     * @return array
     */
    private function orderMethodInterceptors(array $methodInterceptors): array
    {
        usort(
            $methodInterceptors,
            function (MethodInterceptor $methodInterceptor, MethodInterceptor $toCompare) {
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

    /**
     * @param MethodInterceptor $methodInterceptor
     *
     * @return Configuration
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function registerBeforeMethodInterceptor(MethodInterceptor $methodInterceptor): Configuration
    {
        $this->checkIfInterceptorIsCorrect($methodInterceptor);

        $interceptingObject = $methodInterceptor->getInterceptingObject();
        if ($interceptingObject instanceof ServiceActivatorBuilder) {
            $interceptingObject->withPassThroughMessageOnVoidInterface(true);
        }

        $this->beforeCallMethodInterceptors[] = $methodInterceptor;
        $this->beforeCallMethodInterceptors = $this->orderMethodInterceptors($this->beforeCallMethodInterceptors);

        return $this;
    }

    /**
     * @param MethodInterceptor $methodInterceptor
     *
     * @return Configuration
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function registerAfterMethodInterceptor(MethodInterceptor $methodInterceptor): Configuration
    {
        $this->checkIfInterceptorIsCorrect($methodInterceptor);

        if ($methodInterceptor->getInterceptingObject() instanceof ServiceActivatorBuilder) {
            $methodInterceptor->getInterceptingObject()->withPassThroughMessageOnVoidInterface(true);
        }

        $this->afterCallMethodInterceptors[] = $methodInterceptor;
        $this->afterCallMethodInterceptors = $this->orderMethodInterceptors($this->afterCallMethodInterceptors);

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
        $this->messageHandlerBuilderToChannel[$messageHandlerBuilder->getInputMessageChannelName()][] = $messageHandlerBuilder->getEndpointId();
        $this->verifyEndpointAndChannelNameUniqueness();

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
        if (array_key_exists($messageChannelBuilder->getMessageChannelName(), $this->channelBuilders)) {
            throw ConfigurationException::create("Trying to register message channel with name `{$messageChannelBuilder->getMessageChannelName()}` twice.");
        }

        $this->channelBuilders[$messageChannelBuilder->getMessageChannelName()] = $messageChannelBuilder;
        $this->verifyEndpointAndChannelNameUniqueness();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerDefaultChannelFor(MessageChannelBuilder $messageChannelBuilder): Configuration
    {
        $this->defaultChannelBuilders[$messageChannelBuilder->getMessageChannelName()] = $messageChannelBuilder;
        $this->verifyEndpointAndChannelNameUniqueness();

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
        $this->gatewayClassesToGenerateProxies[] = $gatewayBuilder->getInterfaceName();

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
    public function registerInternalGateway(Type $interfaceName): Configuration
    {
        Assert::isTrue($interfaceName->isClassOrInterface(), "Passed internal gateway must be class, passed: {$interfaceName->toString()}");

        $this->gatewayClassesToGenerateProxies[] = $interfaceName->toString();

        return $this;
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

    public function registerServiceDefinition(string|Reference $id, Definition|array $definition = []): Configuration
    {
        if (! isset($this->serviceDefinitions[(string) $id])) {
            if (is_array($definition)) {
                $definition = new Definition((string) $id, $definition);
            }
            $this->serviceDefinitions[(string) $id] = $definition;
        }
        return $this;
    }

    public function registerServiceAlias(string|Reference $id, Reference $aliasTo): Configuration
    {
        if (! isset($this->serviceAliases[(string) $id])) {
            $this->serviceAliases[(string) $id] = $aliasTo;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $builder): void
    {
        $this->prepareAndOptimizeConfiguration($this->interfaceToCallRegistry, $this->applicationConfiguration);

        $messagingBuilder = new MessagingContainerBuilder($builder, $this->interfaceToCallRegistry, $this->applicationConfiguration);

        foreach ($this->serviceDefinitions as $id => $definition) {
            $messagingBuilder->register($id, $definition);
        }

        // TODO: some service configuration should be handled at runtime. Here they are all cached in the container
        //        $messagingBuilder->register('config.defaultSerializationMediaType', MediaType::parseMediaType($this->applicationConfiguration->getDefaultSerializationMediaType()));

        $converters = [];
        foreach ($this->converterBuilders as $converterBuilder) {
            $converters[] = $converterBuilder->compile($messagingBuilder);
        }
        $messagingBuilder->register(ConversionService::REFERENCE_NAME, new Definition(AutoCollectionConversionService::class, ['converters' => $converters], 'createWith'));

        $channelInterceptorsByImportance = $this->channelInterceptorBuilders;
        $channelInterceptorsByChannelName = [];
        foreach ($channelInterceptorsByImportance as $channelInterceptors) {
            /** @var ChannelInterceptorBuilder $channelInterceptor */
            foreach ($channelInterceptors as $channelInterceptor) {
                $channelInterceptorsByChannelName[$channelInterceptor->relatedChannelName()][] = $channelInterceptor;
            }
        }

        foreach ($this->pollingMetadata as $pollingMetadata) {
            $messagingBuilder->register(new PollingMetadataReference($pollingMetadata->getEndpointId()), $pollingMetadata);
        }

        foreach ($this->channelBuilders as $channelsBuilder) {
            $channelReference = new ChannelReference($channelsBuilder->getMessageChannelName());
            $channelDefinition = $channelsBuilder->compile($messagingBuilder);
            $messagingBuilder->register($channelReference, $channelDefinition);
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
                $channelDefinition = $messagingBuilder->getDefinition($channelReference);
                $isPollable = is_a($channelDefinition->getClassName(), PollableChannel::class, true);
                $channelDefinition = new Definition($isPollable ? PollableChannelInterceptorAdapter::class : EventDrivenChannelInterceptorAdapter::class, [
                    $channelDefinition,
                    $interceptorsForChannel,
                ]);
                $messagingBuilder->replace($channelReference, $channelDefinition);
            }
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

        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
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

        foreach ($this->serviceAliases as $id => $aliasTo) {
            $messagingBuilder->replace($id, $aliasTo);
        }

        $messagingBuilder->register(ConfiguredMessagingSystem::class, new Definition(MessagingSystemContainer::class, [new Reference(ContainerInterface::class), $messagingBuilder->getPollingEndpoints(), $gatewayListReferences]));
        (new RegisterSingletonMessagingServices())->process($builder);
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
}
