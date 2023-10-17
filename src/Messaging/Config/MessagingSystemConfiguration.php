<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\AnnotationFinder\AnnotationFinderFactory;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Attribute\WithRequiredReferenceNameList;
use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModuleRetrievingService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\AsynchronousModule;
use Ecotone\Messaging\Config\BeforeSend\BeforeSendChannelInterceptorBuilder;
use Ecotone\Messaging\ConfigurationVariableService;
use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\ConverterBuilder;
use Ecotone\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use Ecotone\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\Bridge\Bridge;
use Ecotone\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterceptedEndpoint;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\InterceptorWithPointCut;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\UninterruptibleServiceActivator;
use Ecotone\Messaging\Handler\Transformer\HeaderEnricher;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Config\BusModule;
use Exception;
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
     * @var AroundInterceptorReference[]
     */
    private array $aroundMethodInterceptors = [];
    /**
     * @var MethodInterceptor[]
     */
    private array $afterCallMethodInterceptors = [];
    /**
     * @var string[]
     */
    private array $requiredReferences = [];
    /**
     * @var string[]
     */
    private array $optionalReferences = [];
    /**
     * @var ConverterBuilder[]
     */
    private array $converterBuilders = [];
    /**
     * @var string[]
     */
    private array $messageConverterReferenceNames = [];
    /**
     * @var InterfaceToCall[]
     */
    private array $interfacesToCall = [];
    private ?ModuleReferenceSearchService $moduleReferenceSearchService;
    private bool $isLazyConfiguration;
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
     * @param object[] $extensionObjects
     * @param string[] $skippedModulesPackages
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

        $this->isLazyConfiguration = ! $serviceConfiguration->isFailingFast();
        $this->applicationConfiguration = $serviceConfiguration;

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
        $this->initialize($moduleConfigurationRetrievingService, $extensionObjects, $preparationInterfaceRegistry, $serviceConfiguration);
    }

    /**
     * @param string[] $skippedModulesPackages
     */
    private function initialize(ModuleRetrievingService $moduleConfigurationRetrievingService, array $serviceExtensions, InterfaceToCallRegistry $preparationInterfaceRegistry, ServiceConfiguration $applicationConfiguration): void
    {
        $moduleReferenceSearchService = ModuleReferenceSearchService::createEmpty();

        $modules = $moduleConfigurationRetrievingService->findAllModuleConfigurations($applicationConfiguration->getSkippedModulesPackages());
        $moduleExtensions = [];

        $extensionObjects = $serviceExtensions;
        foreach ($modules as $module) {
            $extensionObjects = array_merge($extensionObjects, $module->getModuleExtensions($serviceExtensions));
        }
        foreach ($modules as $module) {
            $this->requireReferences($module->getRelatedReferences());

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
                $preparationInterfaceRegistry
            );
        }
        /** This $preparationInterfaceRegistry is only for preparation. We don't want to cache it, as most of the Interface may not be reused anymore. E.g. public method from Eloquent Model */
        $interfaceToCallRegistry = InterfaceToCallRegistry::createWithBackedBy($preparationInterfaceRegistry);

        $this->prepareAndOptimizeConfiguration($interfaceToCallRegistry, $applicationConfiguration);
        $this->gatewayClassesToGenerateProxies = [];

        $this->interfacesToCall = array_unique($this->interfacesToCall);
        $this->moduleReferenceSearchService = $moduleReferenceSearchService;
    }

    /**
     * @param string[] $referenceNames
     *
     * @return Configuration
     */
    public function requireReferences(array $referenceNames): Configuration
    {
        foreach ($referenceNames as $referenceName) {
            $isRequired = true;
            if ($referenceName instanceof RequiredReference) {
                $referenceName = $referenceName->getReferenceName();
            } elseif ($referenceName instanceof OptionalReference) {
                $isRequired = false;
                $referenceName = $referenceName->getReferenceName();
            }

            if (in_array($referenceName, [InterfaceToCallRegistry::REFERENCE_NAME, ConversionService::REFERENCE_NAME])) {
                continue;
            }

            if ($referenceName) {
                if ($isRequired) {
                    $this->requiredReferences[] = $referenceName;
                } else {
                    $this->optionalReferences[] = $referenceName;
                }
            }
        }

        $this->requiredReferences = array_unique($this->requiredReferences);

        return $this;
    }

    private function prepareAndOptimizeConfiguration(InterfaceToCallRegistry $interfaceToCallRegistry, ServiceConfiguration $applicationConfiguration): void
    {
        foreach ($this->channelAdapters as $channelAdapter) {
            $channelAdapter->withEndpointAnnotations(array_merge($channelAdapter->getEndpointAnnotations(), [new AsynchronousRunningEndpoint($channelAdapter->getEndpointId())]));
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
        $this->configureRequiredReferencesAndInterfaces($interfaceToCallRegistry);
        $this->configureInterceptors($interfaceToCallRegistry);

        $this->resolveRequiredReferences($interfaceToCallRegistry, $this->messageHandlerBuilders);
        $this->resolveRequiredReferences($interfaceToCallRegistry, $this->gatewayBuilders);
        $this->resolveRequiredReferences($interfaceToCallRegistry, $this->channelAdapters);
        foreach ($this->channelBuilders as $channelBuilder) {
            $channelBuilder->resolveRelatedInterfaces($interfaceToCallRegistry);
        }
        foreach ($this->channelInterceptorBuilders as $channelInterceptorForName) {
            foreach ($channelInterceptorForName as $channelInterceptorBuilder) {
                $channelInterceptorBuilder->resolveRelatedInterfaces($interfaceToCallRegistry);
            }
        }

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
    }

    /**
     * @param InterceptorWithPointCut[] $interceptors
     * @param InterfaceToCall $interceptedInterface
     * @param object[] $endpointAnnotations
     * @param string[] $requiredInterceptorNames
     *
     * @return InterceptorWithPointCut[]|AroundInterceptorReference[]|MessageHandlerBuilderWithOutputChannel[]
     * @throws MessagingException
     */
    private function getRelatedInterceptors(array $interceptors, InterfaceToCall $interceptedInterface, iterable $endpointAnnotations, iterable $requiredInterceptorNames, InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        $relatedInterceptors = [];
        foreach ($requiredInterceptorNames as $requiredInterceptorName) {
            if (! $this->doesInterceptorWithNameExists($requiredInterceptorName)) {
                throw ConfigurationException::create("Can't find interceptor with name {$requiredInterceptorName} for {$interceptedInterface}");
            }
        }

        foreach ($interceptors as $interceptor) {
            foreach ($requiredInterceptorNames as $requiredInterceptorName) {
                if ($interceptor->hasName($requiredInterceptorName)) {
                    $relatedInterceptors[] = $interceptor;
                    break;
                }
            }

            if ($interceptor->doesItCutWith($interceptedInterface, $endpointAnnotations, $interfaceToCallRegistry)) {
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
        $this->requireReferences($channelInterceptorBuilder->getRequiredReferenceNames());
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
            $this->messageHandlerBuilders[$asynchronousChannel] = ServiceActivatorBuilder::createWithDirectReference(new Bridge(), 'handle')
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
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @param MessageHandlerBuilder[]|InterceptedEndpoint[] $interceptedEndpoints
     */
    public function resolveRequiredReferences(InterfaceToCallRegistry $interfaceToCallRegistry, array $interceptedEndpoints): void
    {
        foreach ($interceptedEndpoints as $interceptedEndpoint) {
            $this->requireReferences(self::resolveRequiredReferenceForBuilder($interceptedEndpoint));

            $relatedInterfaces = $interceptedEndpoint->resolveRelatedInterfaces($interfaceToCallRegistry);
            foreach ($relatedInterfaces as $relatedInterface) {
                foreach ($relatedInterface->getMethodAnnotations() as $methodAnnotation) {
                    if ($methodAnnotation instanceof WithRequiredReferenceNameList) {
                        $this->requireReferences($methodAnnotation->getRequiredReferenceNameList());
                    }
                }
                foreach ($relatedInterface->getClassAnnotations() as $classAnnotation) {
                    if ($classAnnotation instanceof WithRequiredReferenceNameList) {
                        $this->requireReferences($classAnnotation->getRequiredReferenceNameList());
                    }
                }
            }

            if ($interceptedEndpoint instanceof InterceptedEndpoint) {
                $this->interfacesToCall[] = $interceptedEndpoint->getInterceptedInterface($interfaceToCallRegistry);
            }
            $this->interfacesToCall = array_merge($this->interfacesToCall, $relatedInterfaces);
        }
    }

    public static function resolveRequiredReferenceForBuilder(object $builder): array
    {
        $requiredReferences = [];
        if ($builder instanceof RouterBuilder) {
            $requiredReferences = $builder->getRequiredReferenceNames();
        }
        if ($builder instanceof MessageHandlerBuilder) {
            $requiredReferences = $builder->getRequiredReferenceNames();
        }
        if ($builder instanceof ChannelAdapterConsumerBuilder) {
            $requiredReferences = $builder->getRequiredReferences();
        }
        if ($builder instanceof GatewayProxyBuilder) {
            $requiredReferences = $builder->getRequiredReferences();
        }

        if ($builder instanceof MessageHandlerBuilderWithParameterConverters) {
            foreach ($builder->getParameterConverters() as $parameterConverter) {
                $requiredReferences = array_merge($requiredReferences, $parameterConverter->getRequiredReferences());
            }
        }
        if ($builder instanceof InterceptedEndpoint) {
            foreach ($builder->getEndpointAnnotations() as $endpointAnnotation) {
                if ($endpointAnnotation instanceof WithRequiredReferenceNameList) {
                    $requiredReferences = array_merge($requiredReferences, $endpointAnnotation->getRequiredReferenceNameList());
                }
            }
        }

        return array_unique($requiredReferences);
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
            $endpointAnnotations = [new AsynchronousRunningEndpoint('')];
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
        ServiceCacheConfiguration $serviceCacheConfiguration,
        array $userLandClassesToRegister = [],
        bool $enableTestPackage = false
    ): Configuration {
        $cachedVersion = self::getCachedVersion($serviceCacheConfiguration);
        if ($cachedVersion) {
            return $cachedVersion;
        }

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
                array_filter($modulesClasses, fn (string $moduleClassName): bool => class_exists($moduleClassName)),
                $userLandClassesToRegister,
                $enableTestPackage
            ),
            $configurationVariableService,
            $serviceConfiguration,
            $serviceCacheConfiguration
        );
    }

    private static function prepareWithAnnotationFinder(
        AnnotationFinder $annotationFinder,
        ConfigurationVariableService $configurationVariableService,
        ServiceConfiguration $serviceConfiguration,
        ServiceCacheConfiguration $serviceCacheConfiguration
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
            $serviceCacheConfiguration
        );
    }

    public static function getCachedVersion(ServiceCacheConfiguration $serviceCacheConfiguration): ?MessagingSystemConfiguration
    {
        if (! $serviceCacheConfiguration->shouldUseCache()) {
            return null;
        }

        $messagingSystemCachePath = self::getMessagingSystemCachedFile($serviceCacheConfiguration);
        if (file_exists($messagingSystemCachePath)) {
            return unserialize(file_get_contents($messagingSystemCachePath));
        }

        return null;
    }

    /**
     * @TODO that method should stay private, require refactoring tests
     */
    public static function prepareWithModuleRetrievingService(
        ModuleRetrievingService $moduleConfigurationRetrievingService,
        InterfaceToCallRegistry $preparationInterfaceRegistry,
        ServiceConfiguration $applicationConfiguration,
        ServiceCacheConfiguration $serviceCacheConfiguration
    ): MessagingSystemConfiguration {
        self::prepareCacheDirectory($serviceCacheConfiguration);
        $messagingSystemConfiguration = new self(
            $moduleConfigurationRetrievingService,
            $moduleConfigurationRetrievingService->findAllExtensionObjects(),
            $preparationInterfaceRegistry,
            $applicationConfiguration
        );

        if ($serviceCacheConfiguration->shouldUseCache()) {
            $serializedMessagingSystemConfiguration = serialize($messagingSystemConfiguration);
            file_put_contents(self::getMessagingSystemCachedFile($serviceCacheConfiguration), $serializedMessagingSystemConfiguration);
        }

        return $messagingSystemConfiguration;
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

    public static function cleanCache(ServiceCacheConfiguration $serviceCacheConfiguration): void
    {
        self::deleteFiles($serviceCacheConfiguration->getPath(), false);
    }

    private static function deleteFiles(string $target, bool $deleteDirectory): void
    {
        if (is_dir($target)) {
            Assert::isTrue(
                is_writable($target),
                "Not enough permissions to delete from cache directory {$target}"
            );
            $files = glob($target . '*', GLOB_MARK);

            foreach ($files as $file) {
                self::deleteFiles($file, true);
            }

            if ($deleteDirectory) {
                rmdir($target);
            }
        } elseif (is_file($target)) {
            Assert::isTrue(
                is_writable($target),
                "Not enough permissions to delete cache file {$target}"
            );
            unlink($target);
        }
    }

    private static function getMessagingSystemCachedFile(ServiceCacheConfiguration $serviceCacheConfiguration): string
    {
        return $serviceCacheConfiguration->getPath() . DIRECTORY_SEPARATOR . 'messaging_system';
    }

    public function requireConsumer(string $endpointId): Configuration
    {
        $this->requiredConsumerEndpointIds[] = $endpointId;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isLazyLoaded(): bool
    {
        return $this->isLazyConfiguration;
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
        $this->requireReferences($methodInterceptor->getMessageHandler()->getRequiredReferenceNames());

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
     * @param MessageHandlerBuilderWithOutputChannel[] $methodInterceptors
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
        $this->requireReferences($methodInterceptor->getMessageHandler()->getRequiredReferenceNames());

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
        $this->requireReferences($methodInterceptor->getMessageHandler()->getRequiredReferenceNames());

        return $this;
    }

    /**
     * @param AroundInterceptorReference $aroundInterceptorReference
     *
     * @return Configuration
     */
    public function registerAroundMethodInterceptor(AroundInterceptorReference $aroundInterceptorReference): Configuration
    {
        $this->aroundMethodInterceptors[] = $aroundInterceptorReference;
        $this->requireReferences($aroundInterceptorReference->getRequiredReferenceNames());


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
        $this->requireReferences($messageChannelBuilder->getRequiredReferenceNames());
        $this->verifyEndpointAndChannelNameUniqueness();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerDefaultChannelFor(MessageChannelBuilder $messageChannelBuilder): Configuration
    {
        $this->defaultChannelBuilders[$messageChannelBuilder->getMessageChannelName()] = $messageChannelBuilder;
        $this->requireReferences($messageChannelBuilder->getRequiredReferenceNames());
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
        $this->requireReferences($consumerBuilder->getRequiredReferences());

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
        $this->requireReferences($gatewayBuilder->getRequiredReferences());
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
     * @return string[]
     */
    public function getRequiredReferences(): array
    {
        return $this->requiredReferences;
    }

    /**
     * @return string[]
     */
    public function getOptionalReferences(): array
    {
        return $this->optionalReferences;
    }

    /**
     * @inheritDoc
     */
    public function registerRelatedInterfaces(array $relatedInterfaces): Configuration
    {
        $this->interfacesToCall = array_merge($this->interfacesToCall, $relatedInterfaces);

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
    public function registerConverter(ConverterBuilder $converterBuilder): Configuration
    {
        $this->converterBuilders[] = $converterBuilder;
        $this->requireReferences($converterBuilder->getRequiredReferences());

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

    public function buildMessagingSystemFromConfiguration(ReferenceSearchService $referenceSearchService): ConfiguredMessagingSystem
    {
        $interfaceToCallRegistry = InterfaceToCallRegistry::createWithInterfaces($this->interfacesToCall, $this->isLazyConfiguration, $referenceSearchService);
        if (! $this->isLazyConfiguration) {
            $this->prepareAndOptimizeConfiguration($interfaceToCallRegistry, $this->applicationConfiguration);
        }

        $converters = [];
        foreach ($this->converterBuilders as $converterBuilder) {
            $converters[] = $converterBuilder->build($referenceSearchService);
        }
        $referenceSearchService = $this->prepareReferenceSearchServiceWithInternalReferences($referenceSearchService, $converters, $interfaceToCallRegistry);
        /** @var ServiceCacheConfiguration $serviceCacheConfiguration */
        $serviceCacheConfiguration = $referenceSearchService->get(ServiceCacheConfiguration::class);
        self::prepareCacheDirectory($serviceCacheConfiguration);

        $channelInterceptorsByImportance = $this->channelInterceptorBuilders;
        $channelInterceptorsByChannelName = [];
        foreach ($channelInterceptorsByImportance as $channelInterceptors) {
            /** @var ChannelInterceptorBuilder $channelInterceptor */
            foreach ($channelInterceptors as $channelInterceptor) {
                $channelInterceptorsByChannelName[$channelInterceptor->relatedChannelName()][] = $channelInterceptor;
            }
        }

        /** @var GatewayProxyBuilder[][] $preparedGateways */
        $preparedGateways = [];
        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            $preparedGateways[$gatewayBuilder->getReferenceName()][] = $gatewayBuilder->withMessageConverters($this->messageConverterReferenceNames);
        }

        return MessagingSystem::createFrom(
            $referenceSearchService,
            $this->channelBuilders,
            $channelInterceptorsByChannelName,
            $preparedGateways,
            $this->consumerFactories,
            $this->pollingMetadata,
            $this->messageHandlerBuilders,
            $this->channelAdapters,
            $this->consoleCommands
        );
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param array $converters
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     *
     * @return InMemoryReferenceSearchService|ReferenceSearchService
     * @throws MessagingException
     * @throws ReferenceNotFoundException
     */
    private function prepareReferenceSearchServiceWithInternalReferences(ReferenceSearchService $referenceSearchService, array $converters, InterfaceToCallRegistry $interfaceToCallRegistry): InMemoryReferenceSearchService
    {
        return InMemoryReferenceSearchService::createWithReferenceService(
            $referenceSearchService,
            array_merge(
                $this->moduleReferenceSearchService->getAllRegisteredReferences(),
                [
                    ConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith($converters),
                    InterfaceToCallRegistry::REFERENCE_NAME => $interfaceToCallRegistry,
                    ServiceConfiguration::class => $this->applicationConfiguration,
                ]
            ),
            $this->applicationConfiguration
        );
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

    private function configureRequiredReferencesAndInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $this->resolveRequiredReferences(
            $interfaceToCallRegistry,
            array_map(
                function (MethodInterceptor $methodInterceptor) {
                    return $methodInterceptor->getInterceptingObject();
                },
                $this->beforeCallMethodInterceptors
            )
        );
        foreach ($this->beforeCallMethodInterceptors as $interceptor) {
            $this->interfacesToCall = array_merge($this->interfacesToCall, $interceptor->getMessageHandler()->resolveRelatedInterfaces($interfaceToCallRegistry));
        }
        foreach ($this->beforeSendInterceptors as $interceptor) {
            $this->interfacesToCall = array_merge($this->interfacesToCall, $interceptor->getMessageHandler()->resolveRelatedInterfaces($interfaceToCallRegistry));
        }
        foreach ($this->aroundMethodInterceptors as $aroundInterceptorReference) {
            $this->interfacesToCall[] = $aroundInterceptorReference->getInterceptingInterface();
        }
        foreach ($this->afterCallMethodInterceptors as $interceptor) {
            $this->interfacesToCall = array_merge($this->interfacesToCall, $interceptor->getMessageHandler()->resolveRelatedInterfaces($interfaceToCallRegistry));
        }

        $this->resolveRequiredReferences(
            $interfaceToCallRegistry,
            array_map(
                function (MethodInterceptor $methodInterceptor) {
                    return $methodInterceptor->getInterceptingObject();
                },
                $this->afterCallMethodInterceptors
            )
        );
    }
}
