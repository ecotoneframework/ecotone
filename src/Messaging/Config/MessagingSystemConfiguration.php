<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config;

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
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\Bridge\Bridge;
use Ecotone\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ProxyFactory;
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
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Config\BusModule;
use Exception;
use Ramsey\Uuid\Uuid;
use ReflectionException;

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
     * @var PollingMetadata[]
     */
    private array $pollingMetadata = [];
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
    private ?string $rootPathToSearchConfigurationFor;
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
     * Only one instance at time
     *
     * Configuration constructor.
     *
     * @param string|null $rootPathToSearchConfigurationFor
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     * @param object[] $extensionObjects
     * @param ReferenceTypeFromNameResolver $referenceTypeFromNameResolver
     * @param ServiceConfiguration $applicationConfiguration
     *
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     */
    private function __construct(?string $rootPathToSearchConfigurationFor, ModuleRetrievingService $moduleConfigurationRetrievingService, array $extensionObjects, ReferenceTypeFromNameResolver $referenceTypeFromNameResolver, InterfaceToCallRegistry $preparationInterfaceRegistry, ServiceConfiguration $applicationConfiguration)
    {
        $extensionApplicationConfiguration = [];
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof ServiceConfiguration) {
                $extensionApplicationConfiguration[] = $extensionObject;
            }
        }
        $applicationConfiguration = $applicationConfiguration->mergeWith($extensionApplicationConfiguration);
        if (!$applicationConfiguration->getConnectionRetryTemplate()) {
            if ($applicationConfiguration->isProductionConfiguration()) {
                $applicationConfiguration->withConnectionRetryTemplate(
                    RetryTemplateBuilder::exponentialBackoff(1000, 3)
                        ->maxRetryAttempts(5)
                );
            } else {
                $applicationConfiguration->withConnectionRetryTemplate(
                    RetryTemplateBuilder::exponentialBackoff(100, 3)
                        ->maxRetryAttempts(3)
                );
            }
        }

        $this->isLazyConfiguration = !$applicationConfiguration->isFailingFast();
        $this->rootPathToSearchConfigurationFor = $rootPathToSearchConfigurationFor;
        $this->applicationConfiguration = $applicationConfiguration;

        $extensionObjects = array_filter(
            $extensionObjects, function ($extensionObject) {
            if (is_null($extensionObject)) {
                return false;
            }

            return !($extensionObject instanceof ServiceConfiguration);
        }
        );
        $extensionObjects[] = $applicationConfiguration;
        $this->initialize($moduleConfigurationRetrievingService, $extensionObjects, $referenceTypeFromNameResolver, $applicationConfiguration->getCacheDirectoryPath() ? ProxyFactory::createWithCache($applicationConfiguration->getCacheDirectoryPath()) : ProxyFactory::createNoCache(), $preparationInterfaceRegistry, $applicationConfiguration);
    }

    private function initialize(ModuleRetrievingService $moduleConfigurationRetrievingService, array $serviceExtensions, ReferenceTypeFromNameResolver $referenceTypeFromNameResolver, ProxyFactory $proxyFactory, InterfaceToCallRegistry $preparationInterfaceRegistry, ServiceConfiguration $applicationConfiguration): void
    {
        $moduleReferenceSearchService = ModuleReferenceSearchService::createEmpty();
        $moduleReferenceSearchService->store(ProxyFactory::REFERENCE_NAME, $proxyFactory);

        $modules = $moduleConfigurationRetrievingService->findAllModuleConfigurations();
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
        $interfaceToCallRegistry = InterfaceToCallRegistry::createWithBackedBy($referenceTypeFromNameResolver, $preparationInterfaceRegistry);

        $this->prepareAndOptimizeConfiguration($interfaceToCallRegistry, $applicationConfiguration);
        $proxyFactory->warmUpCacheFor($this->gatewayClassesToGenerateProxies);
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

            if (in_array($referenceName, [InterfaceToCallRegistry::REFERENCE_NAME, ConversionService::REFERENCE_NAME, ProxyFactory::REFERENCE_NAME])) {
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

    /**
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @param ServiceConfiguration $applicationConfiguration
     *
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     */
    private function prepareAndOptimizeConfiguration(InterfaceToCallRegistry $interfaceToCallRegistry, ServiceConfiguration $applicationConfiguration): void
    {
        $pollableEndpointAnnotations = [new AsynchronousRunningEndpoint()];
        foreach ($this->channelAdapters as $channelAdapter) {
            $channelAdapter->withEndpointAnnotations(array_merge($channelAdapter->getEndpointAnnotations(), $pollableEndpointAnnotations));
        }

        /** @var BeforeSendChannelInterceptorBuilder[] $beforeSendInterceptors */
        $beforeSendInterceptors = [];
        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            if ($messageHandlerBuilder instanceof MessageHandlerBuilderWithOutputChannel) {
                if ($this->beforeSendInterceptors) {
                    $interceptorWithPointCuts = $this->getRelatedInterceptors($this->beforeSendInterceptors, $messageHandlerBuilder->getInterceptedInterface($interfaceToCallRegistry), $messageHandlerBuilder->getEndpointAnnotations(), $messageHandlerBuilder->getRequiredInterceptorNames());
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

        $this->configureAsynchronousEndpoints();
        $this->configureDefaultMessageChannels();

        $this->resolveRequiredReferences(
            $interfaceToCallRegistry,
            array_map(
                function (MethodInterceptor $methodInterceptor) {
                    return $methodInterceptor->getInterceptingObject();
                }, $this->beforeCallMethodInterceptors
            )
        );
        foreach ($this->aroundMethodInterceptors as $aroundInterceptorReference) {
            $this->interfacesToCall[] = $aroundInterceptorReference->getInterceptingInterface($interfaceToCallRegistry);
        }
        $this->resolveRequiredReferences(
            $interfaceToCallRegistry,
            array_map(
                function (MethodInterceptor $methodInterceptor) {
                    return $methodInterceptor->getInterceptingObject();
                }, $this->afterCallMethodInterceptors
            )
        );
        foreach ($this->messageHandlerBuilders as $key => $messageHandlerBuilder) {
            if ($this->channelBuilders[$messageHandlerBuilder->getInputMessageChannelName()]->isPollable() && ($messageHandlerBuilder instanceof InterceptedEndpoint)) {
                $this->messageHandlerBuilders[$key] = $messageHandlerBuilder->withEndpointAnnotations(array_merge($messageHandlerBuilder->getEndpointAnnotations(), $pollableEndpointAnnotations));
            }
        }
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
            if (!array_key_exists($requiredConsumerEndpointId, $this->messageHandlerBuilders) && !array_key_exists($requiredConsumerEndpointId, $this->channelAdapters)) {
                throw ConfigurationException::create("Consumer with id {$requiredConsumerEndpointId} has no configuration defined. Define consumer configuration and retry.");
            }
        }
        foreach ($this->pollingMetadata as $pollingMetadata) {
            if (!$this->hasMessageHandlerWithName($pollingMetadata) && !$this->hasChannelAdapterWithName($pollingMetadata)) {
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
    private function getRelatedInterceptors($interceptors, InterfaceToCall $interceptedInterface, iterable $endpointAnnotations, iterable $requiredInterceptorNames): iterable
    {
        $relatedInterceptors = [];
        foreach ($requiredInterceptorNames as $requiredInterceptorName) {
            if (!$this->doesInterceptorWithNameExists($requiredInterceptorName)) {
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

            if ($interceptor->doesItCutWith($interceptedInterface, $endpointAnnotations)) {
                $relatedInterceptors[] = $interceptor->addInterceptedInterfaceToCall($interceptedInterface, $endpointAnnotations);
            }
        }

        return array_unique($relatedInterceptors);
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

        return $this;
    }

    /**
     * @return void
     */
    private function configureAsynchronousEndpoints(): void
    {
        $asynchronousChannels = [];

        foreach ($this->asynchronousEndpoints as $targetEndpointId => $asynchronousMessageChannel) {
            if (!isset($this->channelBuilders[$asynchronousMessageChannel]) && !isset($this->defaultChannelBuilders[$asynchronousMessageChannel])) {
                throw ConfigurationException::create("Registered asynchronous endpoint `{$targetEndpointId}`, however channel configuration for `{$asynchronousMessageChannel}` was not provided.");
            }

            $foundEndpoint = false;
            $asynchronousChannels[] = $asynchronousMessageChannel;
            foreach ($this->messageHandlerBuilders as $key => $messageHandlerBuilder) {
                if ($messageHandlerBuilder->getEndpointId() === $targetEndpointId) {
                    $originalInputChannelName = $messageHandlerBuilder->getInputMessageChannelName();
                    $targetChannelName        = AsynchronousModule::getSynchronousChannelName($originalInputChannelName);
                    $this->messageHandlerBuilders[$key] = $messageHandlerBuilder->withInputChannelName($targetChannelName);

                    $this->messageHandlerBuilders[$targetChannelName] = (
                    TransformerBuilder::createHeaderEnricher(
                        [
                            BusModule::COMMAND_CHANNEL_NAME_BY_NAME => null,
                            BusModule::COMMAND_CHANNEL_NAME_BY_OBJECT => null,
                            BusModule::EVENT_CHANNEL_NAME_BY_OBJECT => null,
                            BusModule::EVENT_CHANNEL_NAME_BY_NAME => null,
                            MessageHeaders::REPLY_CHANNEL => null,
                            MessageHeaders::ROUTING_SLIP => $targetChannelName
                        ]
                    )
                        ->withEndpointId($targetChannelName)
                        ->withInputChannelName($originalInputChannelName)
                        ->withOutputMessageChannel($asynchronousMessageChannel)
                    );

                    if (array_key_exists($messageHandlerBuilder->getEndpointId(), $this->pollingMetadata)) {
                        $this->pollingMetadata[$targetChannelName] = $this->pollingMetadata[$messageHandlerBuilder->getEndpointId()];
                        unset($this->pollingMetadata[$messageHandlerBuilder->getEndpointId()]);
                    }
                    $foundEndpoint = true;
                    break;
                }
            }

            if (!$foundEndpoint) {
                throw ConfigurationException::create("Registered asynchronous endpoint for not existing id {$targetEndpointId}");
            }
        }

        foreach (array_unique($asynchronousChannels) as $asynchronousChannel) {
            //        needed for correct around intercepting, otherwise requestReply is outside of around interceptor scope
            $bridgeBuilder = ChainMessageHandlerBuilder::create()
                ->chain(ServiceActivatorBuilder::createWithDirectReference(new Bridge(), "handle"))
                ->chain(ServiceActivatorBuilder::createWithDirectReference(new Bridge(), "handle"));
            $this->messageHandlerBuilders[$asynchronousChannel] = $bridgeBuilder
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
            if (!array_key_exists($messageHandlerBuilder->getInputMessageChannelName(), $this->channelBuilders)) {
                if (array_key_exists($messageHandlerBuilder->getInputMessageChannelName(), $this->defaultChannelBuilders)) {
                    $this->channelBuilders[$messageHandlerBuilder->getInputMessageChannelName()] = $this->defaultChannelBuilders[$messageHandlerBuilder->getInputMessageChannelName()];
                } else {
                    $this->channelBuilders[$messageHandlerBuilder->getInputMessageChannelName()] = SimpleMessageChannelBuilder::createDirectMessageChannel($messageHandlerBuilder->getInputMessageChannelName());
                }
            }
        }

        foreach ($this->defaultChannelBuilders as $name => $defaultChannelBuilder) {
            if (!array_key_exists($name, $this->channelBuilders)) {
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
                    $beforeCallInterceptors = $this->getRelatedInterceptors($this->beforeCallMethodInterceptors, $messageHandlerBuilder->getInterceptedInterface($interfaceRegistry), $messageHandlerBuilder->getEndpointAnnotations(), $messageHandlerBuilder->getRequiredInterceptorNames());
                }
                if ($this->aroundMethodInterceptors) {
                    $aroundInterceptors = $this->getRelatedInterceptors($this->aroundMethodInterceptors, $messageHandlerBuilder->getInterceptedInterface($interfaceRegistry), $messageHandlerBuilder->getEndpointAnnotations(), $messageHandlerBuilder->getRequiredInterceptorNames());
                }
                if ($this->afterCallMethodInterceptors) {
                    $afterCallInterceptors = $this->getRelatedInterceptors($this->afterCallMethodInterceptors, $messageHandlerBuilder->getInterceptedInterface($interfaceRegistry), $messageHandlerBuilder->getEndpointAnnotations(), $messageHandlerBuilder->getRequiredInterceptorNames());
                }

                foreach ($aroundInterceptors as $aroundInterceptorReference) {
                    $messageHandlerBuilder = $messageHandlerBuilder->addAroundInterceptor($aroundInterceptorReference);
                    $this->messageHandlerBuilders[$messageHandlerBuilder->getEndpointId()] = $messageHandlerBuilder;
                }
                if ($beforeCallInterceptors || $afterCallInterceptors) {
                    $outputChannel = $messageHandlerBuilder->getOutputMessageChannelName();
                    $messageHandlerBuilder = $messageHandlerBuilder->withOutputMessageChannel("");
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
                $beforeCallInterceptors = $this->getRelatedInterceptors($this->beforeCallMethodInterceptors, $gatewayBuilder->getInterceptedInterface($interfaceRegistry), $gatewayBuilder->getEndpointAnnotations(), $gatewayBuilder->getRequiredInterceptorNames());
            }
            if ($this->aroundMethodInterceptors) {
                $aroundInterceptors = $this->getRelatedInterceptors($this->aroundMethodInterceptors, $gatewayBuilder->getInterceptedInterface($interfaceRegistry), $gatewayBuilder->getEndpointAnnotations(), $gatewayBuilder->getRequiredInterceptorNames());
            }
            if ($this->afterCallMethodInterceptors) {
                $afterCallInterceptors = $this->getRelatedInterceptors($this->afterCallMethodInterceptors, $gatewayBuilder->getInterceptedInterface($interfaceRegistry), $gatewayBuilder->getEndpointAnnotations(), $gatewayBuilder->getRequiredInterceptorNames());
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
                $beforeCallInterceptors = $this->getRelatedInterceptors($this->beforeCallMethodInterceptors, $channelAdapter->getInterceptedInterface($interfaceRegistry), $channelAdapter->getEndpointAnnotations(), $channelAdapter->getRequiredInterceptorNames());
            }
            if ($this->aroundMethodInterceptors) {
                $aroundInterceptors = $this->getRelatedInterceptors($this->aroundMethodInterceptors, $channelAdapter->getInterceptedInterface($interfaceRegistry), $channelAdapter->getEndpointAnnotations(), $channelAdapter->getRequiredInterceptorNames());
            }
            if ($this->afterCallMethodInterceptors) {
                $afterCallInterceptors = $this->getRelatedInterceptors($this->afterCallMethodInterceptors, $channelAdapter->getInterceptedInterface($interfaceRegistry), $channelAdapter->getEndpointAnnotations(), $channelAdapter->getRequiredInterceptorNames());
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

        if ($this->applicationConfiguration->getDefaultErrorChannel() && $pollingMetadata->isErrorChannelEnabled() && !$pollingMetadata->getErrorChannelName()) {
            $pollingMetadata = $pollingMetadata
                ->setErrorChannelName($this->applicationConfiguration->getDefaultErrorChannel());
        }
        if ($this->applicationConfiguration->getDefaultMemoryLimitInMegabytes() && !$pollingMetadata->getMemoryLimitInMegabytes()) {
            $pollingMetadata = $pollingMetadata
                ->setMemoryLimitInMegaBytes($this->applicationConfiguration->getDefaultMemoryLimitInMegabytes());
        }
        if ($this->applicationConfiguration->getConnectionRetryTemplate() && !$pollingMetadata->getConnectionRetryTemplate()) {
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

    public static function prepareWithDefaults(ModuleRetrievingService $moduleConfigurationRetrievingService): MessagingSystemConfiguration
    {
        return new self(null, $moduleConfigurationRetrievingService, $moduleConfigurationRetrievingService->findAllExtensionObjects(), InMemoryReferenceTypeFromNameResolver::createEmpty(), InterfaceToCallRegistry::createEmpty(), ServiceConfiguration::createWithDefaults());
    }

    public static function prepare(string $rootPathToSearchConfigurationFor, ReferenceTypeFromNameResolver $referenceTypeFromNameResolver, ConfigurationVariableService $configurationVariableService, ServiceConfiguration $applicationConfiguration, bool $useCachedVersion): Configuration
    {
        if ($useCachedVersion) {
            Assert::isTrue((bool)$applicationConfiguration->getCacheDirectoryPath(), "Can't make use of cached version of messaging if no cache path is defined");
            $cachedVersion = self::getCachedVersion($applicationConfiguration);
            if ($cachedVersion) {
                return $cachedVersion;
            }
        }

        $annotationFinder = AnnotationFinderFactory::createForAttributes(
            realpath($rootPathToSearchConfigurationFor),
            $applicationConfiguration->getNamespaces(),
            $applicationConfiguration->getEnvironment(),
            $applicationConfiguration->getLoadedCatalog() ?? ""
        );

        $preparationInterfaceRegistry = InterfaceToCallRegistry::createWith($referenceTypeFromNameResolver, $annotationFinder);
        return self::prepareWithModuleRetrievingService(
            $rootPathToSearchConfigurationFor,
            new AnnotationModuleRetrievingService(
                $annotationFinder,
                $preparationInterfaceRegistry,
                $configurationVariableService
            ),
            $referenceTypeFromNameResolver,
            $preparationInterfaceRegistry,
            $applicationConfiguration
        );
    }

    private static function getCachedVersion(ServiceConfiguration $applicationConfiguration): ?MessagingSystemConfiguration
    {
        if (!$applicationConfiguration->getCacheDirectoryPath()) {
            return null;
        }

        $messagingSystemCachePath = $applicationConfiguration->getCacheDirectoryPath() . DIRECTORY_SEPARATOR . "messaging_system";
        if (file_exists($messagingSystemCachePath)) {
            return unserialize(file_get_contents($messagingSystemCachePath));
        }

        return null;
    }

    public static function prepareWithModuleRetrievingService(?string $rootProjectDirectoryPath, ModuleRetrievingService $moduleConfigurationRetrievingService, ReferenceTypeFromNameResolver $referenceTypeFromNameResolver, InterfaceToCallRegistry $preparationInterfaceRegistry, ServiceConfiguration $applicationConfiguration): MessagingSystemConfiguration
    {
        $cacheDirectoryPath = $applicationConfiguration->getCacheDirectoryPath();
        if ($cacheDirectoryPath) {
            self::prepareCacheDirectory($cacheDirectoryPath);
        }
        $messagingSystemConfiguration = new self($rootProjectDirectoryPath, $moduleConfigurationRetrievingService, $moduleConfigurationRetrievingService->findAllExtensionObjects(), $referenceTypeFromNameResolver, $preparationInterfaceRegistry, $applicationConfiguration);

        if ($cacheDirectoryPath) {
            $serializedMessagingSystemConfiguration = serialize($messagingSystemConfiguration);
            file_put_contents($cacheDirectoryPath . DIRECTORY_SEPARATOR . "messaging_system", $serializedMessagingSystemConfiguration);
        }

        return $messagingSystemConfiguration;
    }

    private static function prepareCacheDirectory(string $cacheDirectoryPath): void
    {
        if (!is_dir($cacheDirectoryPath)) {
            @mkdir($cacheDirectoryPath, 0775, true);
        }
        Assert::isTrue(is_writable($cacheDirectoryPath), "Not enough permissions to write into cache directory {$cacheDirectoryPath}");

        Assert::isFalse(is_file($cacheDirectoryPath), "Cache directory is file, should be directory");
    }

    public static function cleanCache(string $cacheDirectoryPath): void
    {
        if ($cacheDirectoryPath) {
            self::prepareCacheDirectory($cacheDirectoryPath);
            self::deleteFiles($cacheDirectoryPath . DIRECTORY_SEPARATOR, false);
        }
    }

    private static function deleteFiles(string $target, bool $deleteDirectory): void
    {
        if (is_dir($target)) {
            $files = glob($target . '*', GLOB_MARK);

            foreach ($files as $file) {
                self::deleteFiles($file, true);
            }

            if ($deleteDirectory) {
                rmdir($target);
            }
        } elseif (is_file($target)) {
            unlink($target);
        }
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
            $methodInterceptors, function (MethodInterceptor $methodInterceptor, MethodInterceptor $toCompare) {
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
    public function registerAsynchronousEndpoint(string $asynchronousChannelName, string $targetEndpointId): Configuration
    {
        $this->asynchronousEndpoints[$targetEndpointId] = $asynchronousChannelName;

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
        if (is_null($messageHandlerBuilder->getEndpointId()) || $messageHandlerBuilder->getEndpointId() === "") {
            $messageHandlerBuilder->withEndpointId(Uuid::uuid4()->toString());
        }
        if (array_key_exists($messageHandlerBuilder->getEndpointId(), $this->messageHandlerBuilders)) {
            throw ConfigurationException::create("Trying to register endpoints with same id {$messageHandlerBuilder->getEndpointId()}. {$messageHandlerBuilder} and {$this->messageHandlerBuilders[$messageHandlerBuilder->getEndpointId()]}");
        }
        if ($messageHandlerBuilder->getInputMessageChannelName() === $messageHandlerBuilder->getEndpointId()) {
            throw ConfigurationException::create("Can't register message handler {$messageHandlerBuilder} with same endpointId as inputChannelName.");
        }

        $this->messageHandlerBuilders[$messageHandlerBuilder->getEndpointId()] = $messageHandlerBuilder;
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
     * @param GatewayBuilder $gatewayBuilder
     *
     * @return Configuration
     */
    public function registerGatewayBuilder(GatewayBuilder $gatewayBuilder): Configuration
    {
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
        if (!$this->isLazyConfiguration) {
            $this->prepareAndOptimizeConfiguration($interfaceToCallRegistry, $this->applicationConfiguration);
        }

        $converters = [];
        foreach ($this->converterBuilders as $converterBuilder) {
            $converters[] = $converterBuilder->build($referenceSearchService);
        }
        $referenceSearchService = $this->prepareReferenceSearchServiceWithInternalReferences($referenceSearchService, $converters, $interfaceToCallRegistry);
        /** @var ProxyFactory $proxyFactory */
        $proxyFactory = $referenceSearchService->get(ProxyFactory::REFERENCE_NAME);
        $proxyFactory->warmUpCacheFor($this->gatewayClassesToGenerateProxies);
        spl_autoload_register($proxyFactory->getConfiguration()->getProxyAutoloader());

        $channelInterceptorsByImportance = $this->channelInterceptorBuilders;
        arsort($channelInterceptorsByImportance);
        $channelInterceptorsByChannelName = [];
        foreach ($channelInterceptorsByImportance as $channelInterceptors) {
            /** @var ChannelInterceptorBuilder $channelInterceptor */
            foreach ($channelInterceptors as $channelInterceptor) {
                $channelInterceptorsByChannelName[$channelInterceptor->relatedChannelName()][] = $channelInterceptor;
            }
        }

        /** @var GatewayBuilder[][] $preparedGateways */
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
            $this->isLazyConfiguration,
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
                    ServiceConfiguration::class => $this->applicationConfiguration
                ]
            )
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
}