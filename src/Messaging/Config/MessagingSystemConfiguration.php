<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Ecotone\Messaging\Annotation\WithRequiredReferenceNameList;
use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModuleRetrievingService;
use Ecotone\Messaging\Config\Annotation\FileSystemAnnotationRegistrationService;
use Ecotone\Messaging\Config\Lazy\LazyMessagingSystem;
use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\ConverterBuilder;
use Ecotone\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use Ecotone\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\Bridge\BridgeBuilder;
use Ecotone\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayBuilder;
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
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Exception;
use Ramsey\Uuid\Uuid;
use ReflectionException;

/**
 * Class Configuration
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MessagingSystemConfiguration implements Configuration
{
    /**
     * @var MessageChannelBuilder[]
     */
    private $channelBuilders = [];
    /**
     * @var MessageChannelBuilder[]
     */
    private $defaultChannelBuilders = [];
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
    private $pollingMetadata = [];
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
     * @var MethodInterceptor[]
     */
    private $beforeSendInterceptors = [];
    /**
     * @var MethodInterceptor[]
     */
    private $beforeCallMethodInterceptors = [];
    /**
     * @var AroundInterceptorReference[]
     */
    private $aroundMethodInterceptors = [];
    /**
     * @var MethodInterceptor[]
     */
    private $afterCallMethodInterceptors = [];
    /**
     * @var string[]
     */
    private $requiredReferences = [];
    /**
     * @var string[]
     */
    private $optionalReferences = [];
    /**
     * @var ConverterBuilder[]
     */
    private $converterBuilders = [];
    /**
     * @var string[]
     */
    private $messageConverterReferenceNames = [];
    /**
     * @var InterfaceToCall[]
     */
    private $interfacesToCall = [];
    /**
     * @var ModuleReferenceSearchService
     */
    private $moduleReferenceSearchService;
    /**
     * @var bool
     */
    private $isLazyConfiguration;
    /**
     * @var array
     */
    private $asynchronousEndpoints = [];
    /**
     * @var string[]
     */
    private $gatewayClassesToGenerateProxies = [];
    /**
     * @var string
     */
    private $rootPathToSearchConfigurationFor;

    /**
     * Only one instance at time
     *
     * Configuration constructor.
     * @param string|null $rootPathToSearchConfigurationFor
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     * @param object[] $extensionObjects
     * @param ReferenceTypeFromNameResolver $referenceTypeFromNameResolver
     * @param bool $isLazyLoaded
     * @param ProxyFactory $proxyFactory
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     */
    private function __construct(?string $rootPathToSearchConfigurationFor, ModuleRetrievingService $moduleConfigurationRetrievingService, array $extensionObjects, ReferenceTypeFromNameResolver $referenceTypeFromNameResolver, bool $isLazyLoaded, ProxyFactory $proxyFactory)
    {
        $this->isLazyConfiguration = $isLazyLoaded;
        $this->rootPathToSearchConfigurationFor = $rootPathToSearchConfigurationFor;
        $this->initialize($moduleConfigurationRetrievingService, $extensionObjects, $referenceTypeFromNameResolver, $proxyFactory);
    }

    /**
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     * @param object[] $extensionObjects
     * @param ReferenceTypeFromNameResolver $referenceTypeFromNameResolver
     * @param ProxyFactory $proxyFactory
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     */
    private function initialize(ModuleRetrievingService $moduleConfigurationRetrievingService, array $extensionObjects, ReferenceTypeFromNameResolver $referenceTypeFromNameResolver, ProxyFactory $proxyFactory): void
    {
        $moduleReferenceSearchService = ModuleReferenceSearchService::createEmpty();
        $moduleReferenceSearchService->store(ProxyFactory::REFERENCE_NAME, $proxyFactory);

        $modules = $moduleConfigurationRetrievingService->findAllModuleConfigurations();
        $moduleExtensions = [];

        foreach ($modules as $module) {
            $this->requireReferences($module->getRelatedReferences());

            $moduleExtensions[$module->getName()] = [];
            foreach ($extensionObjects as $extensionObject) {
                if ($module->canHandle($extensionObject)) {
                    $moduleExtensions[$module->getName()][] = $extensionObject;
                }
            }
        }

        foreach ($modules as $module) {
            $module->prepare(
                $this,
                $moduleExtensions[$module->getName()],
                $moduleReferenceSearchService
            );
        }
        $interfaceToCallRegistry = InterfaceToCallRegistry::createWith($referenceTypeFromNameResolver);

        $this->prepareAndOptimizeConfiguration($interfaceToCallRegistry);
        if ($this->isLazyConfiguration) {
            $proxyFactory->warmUpCacheFor($this->gatewayClassesToGenerateProxies);
            $this->gatewayClassesToGenerateProxies = [];
        }

        $this->interfacesToCall = array_unique($this->interfacesToCall);
        $this->moduleReferenceSearchService = $moduleReferenceSearchService;
    }

    /**
     * @param string[] $referenceNames
     * @return Configuration
     */
    public function requireReferences(array $referenceNames): Configuration
    {
        foreach ($referenceNames as $referenceName) {
            $isRequired = true;
            if ($referenceName instanceof RequiredReference) {
                $referenceName = $referenceName->getReferenceName();
            }elseif ($referenceName instanceof OptionalReference) {
                $isRequired = false;
                $referenceName = $referenceName->getReferenceName();
            }

            if (in_array($referenceName, [InterfaceToCallRegistry::REFERENCE_NAME, ConversionService::REFERENCE_NAME, ProxyFactory::REFERENCE_NAME])) {
                continue;
            }

            if ($referenceName) {
                if ($isRequired) {
                    $this->requiredReferences[] = $referenceName;
                }else {
                    $this->optionalReferences[] = $referenceName;
                }
            }
        }

        $this->requiredReferences = array_unique($this->requiredReferences);

        return $this;
    }

    /**
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    private function prepareAndOptimizeConfiguration(InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $this->configureAsynchronousEndpoints();
        $this->configureDefaultMessageChannels();
        $this->resolveRequiredReferences($interfaceToCallRegistry,
            array_map(function (MethodInterceptor $methodInterceptor) {
                return $methodInterceptor->getInterceptingObject();
            }, $this->beforeCallMethodInterceptors)
        );
        foreach ($this->aroundMethodInterceptors as $aroundInterceptorReference) {
            $this->interfacesToCall[] = $aroundInterceptorReference->getInterceptingInterface($interfaceToCallRegistry);
        }
        $this->resolveRequiredReferences($interfaceToCallRegistry,
            array_map(function (MethodInterceptor $methodInterceptor) {
                return $methodInterceptor->getInterceptingObject();
            }, $this->afterCallMethodInterceptors)
        );
        $this->configureInterceptors($interfaceToCallRegistry);
        $this->resolveRequiredReferences($interfaceToCallRegistry, $this->messageHandlerBuilders);
        $this->resolveRequiredReferences($interfaceToCallRegistry, $this->gatewayBuilders);
        $this->resolveRequiredReferences($interfaceToCallRegistry, $this->channelAdapters);
    }

    /**
     * @return void
     */
    private function configureAsynchronousEndpoints(): void
    {
        $messageHandlerBuilders = $this->messageHandlerBuilders;
        $asynchronousChannels = [];

        foreach ($this->asynchronousEndpoints as $targetEndpointId => $asynchronousMessageChannel) {
            $asynchronousChannels[] = $asynchronousMessageChannel;
            foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
                if ($messageHandlerBuilder->getEndpointId() === $targetEndpointId) {
                    $targetChannelName = $messageHandlerBuilder->getInputMessageChannelName() . ".target";
                    $messageHandlerBuilders[] = TransformerBuilder::createHeaderEnricher([
                        MessageHeaders::ROUTING_SLIP => $targetChannelName
                    ])
                        ->withEndpointId($targetChannelName)
                        ->withInputChannelName($messageHandlerBuilder->getInputMessageChannelName())
                        ->withOutputMessageChannel($asynchronousMessageChannel);
                    $messageHandlerBuilder->withInputChannelName($targetChannelName);

                    if (array_key_exists($messageHandlerBuilder->getEndpointId(), $this->pollingMetadata)) {
                        $this->pollingMetadata[$targetChannelName] = $this->pollingMetadata[$messageHandlerBuilder->getEndpointId()];
                        unset($this->pollingMetadata[$messageHandlerBuilder->getEndpointId()]);
                    }
                    break;
                }
            }
        }

        foreach (array_unique($asynchronousChannels) as $asychronousChannel) {
            $messageHandlerBuilders[] = BridgeBuilder::create()
                ->withEndpointId($asychronousChannel)
                ->withInputChannelName($asychronousChannel);
        }

        $this->messageHandlerBuilders = $messageHandlerBuilders;
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
    }

    /**
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @param MessageHandlerBuilder[]|InterceptedEndpoint[] $interceptedEndpoints
     */
    public function resolveRequiredReferences(InterfaceToCallRegistry $interfaceToCallRegistry, array $interceptedEndpoints): void
    {
        foreach ($interceptedEndpoints as $interceptedEndpoint) {
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

    /**
     * @param InterfaceToCallRegistry $interfaceRegistry
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
                    $messageHandlerBuilder->addAroundInterceptor($aroundInterceptorReference);
                }
                if ($beforeCallInterceptors || $afterCallInterceptors) {
                    $messageHandlerBuilderToUse = ChainMessageHandlerBuilder::create()
                        ->withEndpointId($messageHandlerBuilder->getEndpointId())
                        ->withInputChannelName($messageHandlerBuilder->getInputMessageChannelName())
                        ->withOutputMessageChannel($messageHandlerBuilder->getOutputMessageChannelName());

                    foreach ($beforeCallInterceptors as $beforeCallInterceptor) {
                        $messageHandlerBuilderToUse->chain($beforeCallInterceptor->getInterceptingObject());
                    }
                    $messageHandlerBuilderToUse->chain($messageHandlerBuilder);
                    foreach ($afterCallInterceptors as $afterCallInterceptor) {
                        $messageHandlerBuilderToUse->chain($afterCallInterceptor->getInterceptingObject());
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

    /**
     * @param InterceptorWithPointCut[] $interceptors
     * @param InterfaceToCall $interceptedInterface
     * @param object[] $endpointAnnotations
     * @param string[] $requiredInterceptorNames
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
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     * @return Configuration
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     */
    public static function prepare(ModuleRetrievingService $moduleConfigurationRetrievingService): Configuration
    {
        return new self(null, $moduleConfigurationRetrievingService, $moduleConfigurationRetrievingService->findAllExtensionObjects(), InMemoryReferenceTypeFromNameResolver::createEmpty(), false, ProxyFactory::createNoCache());
    }

    /**
     * @param string $rootPathToSearchConfigurationFor
     * @param array $namespaces
     * @param ReferenceTypeFromNameResolver $referenceTypeFromNameResolver
     * @param string $environment
     * @param bool $isLazyLoaded
     * @param bool $loadSrc
     * @param ProxyFactory $proxyFactory
     * @return Configuration
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     */
    public static function createWithCachedReferenceObjectsForNamespaces(string $rootPathToSearchConfigurationFor, array $namespaces, ReferenceTypeFromNameResolver $referenceTypeFromNameResolver, string $environment, bool $isLazyLoaded, bool $loadSrc, ProxyFactory $proxyFactory): Configuration
    {
        self::registerAnnotationAutoloader($rootPathToSearchConfigurationFor);

        return MessagingSystemConfiguration::prepareWithCachedReferenceObjects(
            $rootPathToSearchConfigurationFor,
            new AnnotationModuleRetrievingService(
                new FileSystemAnnotationRegistrationService(
                    new AnnotationReader(),
                    realpath($rootPathToSearchConfigurationFor),
                    $namespaces,
                    $environment,
                    $loadSrc
                )
            ),
            $referenceTypeFromNameResolver,
            $isLazyLoaded,
            $proxyFactory
        );
    }

    /**
     * @param string|null $rootProjectDirectoryPath
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     * @param ReferenceTypeFromNameResolver $referenceTypeFromNameResolver
     * @param bool $isLazyLoaded
     * @param ProxyFactory $proxyFactory
     * @return Configuration
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     */
    public static function prepareWithCachedReferenceObjects(?string $rootProjectDirectoryPath, ModuleRetrievingService $moduleConfigurationRetrievingService, ReferenceTypeFromNameResolver $referenceTypeFromNameResolver, bool $isLazyLoaded, ProxyFactory $proxyFactory): Configuration
    {
        return new self($rootProjectDirectoryPath, $moduleConfigurationRetrievingService, $moduleConfigurationRetrievingService->findAllExtensionObjects(), $referenceTypeFromNameResolver, $isLazyLoaded, $proxyFactory);
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
     * @return Configuration
     */
    public function registerPollingMetadata(PollingMetadata $pollingMetadata): Configuration
    {
        $this->pollingMetadata[$pollingMetadata->getEndpointId()] = $pollingMetadata;

        return $this;
    }

    /**
     * @param MethodInterceptor $methodInterceptor
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
     * @return array
     */
    private function orderMethodInterceptors(array $methodInterceptors): array
    {
        usort($methodInterceptors, function (MethodInterceptor $methodInterceptor, MethodInterceptor $toCompare) {
            if ($methodInterceptor->getPrecedence() === $toCompare->getPrecedence()) {
                return 0;
            }

            if ($methodInterceptor->getPrecedence() > $toCompare->getPrecedence()) {
                return 1;
            }

            return -1;
        });

        return $methodInterceptors;
    }

    /**
     * @param MethodInterceptor $methodInterceptor
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
     * @inheritDoc
     */
    public function registerChannelInterceptor(ChannelInterceptorBuilder $channelInterceptorBuilder): Configuration
    {
        $this->channelInterceptorBuilders[$channelInterceptorBuilder->getPrecedence()][] = $channelInterceptorBuilder;
        $this->requireReferences($channelInterceptorBuilder->getRequiredReferenceNames());

        return $this;
    }

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
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

        $this->requireReferences($messageHandlerBuilder->getRequiredReferenceNames());

        if ($messageHandlerBuilder instanceof MessageHandlerBuilderWithParameterConverters) {
            foreach ($messageHandlerBuilder->getParameterConverters() as $parameterConverter) {
                $this->requireReferences($parameterConverter->getRequiredReferences());
            }
        }
        if ($messageHandlerBuilder instanceof MessageHandlerBuilderWithOutputChannel) {
            foreach ($messageHandlerBuilder->getEndpointAnnotations() as $endpointAnnotation) {
                if ($endpointAnnotation instanceof WithRequiredReferenceNameList) {
                    $this->requireReferences($endpointAnnotation->getRequiredReferenceNameList());
                }
            }
        }

        $this->messageHandlerBuilders[$messageHandlerBuilder->getEndpointId()] = $messageHandlerBuilder;

        return $this;
    }

    /**
     * @param MessageChannelBuilder $messageChannelBuilder
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

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerDefaultChannelFor(MessageChannelBuilder $messageChannelBuilder): Configuration
    {
        $this->defaultChannelBuilders[$messageChannelBuilder->getMessageChannelName()] = $messageChannelBuilder;
        $this->requireReferences($messageChannelBuilder->getRequiredReferenceNames());

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerConsumer(ChannelAdapterConsumerBuilder $consumerBuilder): Configuration
    {
        $this->channelAdapters[] = $consumerBuilder;
        $this->requireReferences($consumerBuilder->getRequiredReferences());

        return $this;
    }

    /**
     * @param GatewayBuilder $gatewayBuilder
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
    public function registerInternalGateway(TypeDescriptor $interfaceName): Configuration
    {
        Assert::isTrue($interfaceName->isClass(), "Passed internal gateway must be class, passed: {$interfaceName->toString()}");

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


    /**
     * Initialize messaging system from current configuration
     *
     * @param ReferenceSearchService $referenceSearchService
     * @return ConfiguredMessagingSystem
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     */
    public function buildMessagingSystemFromConfiguration(ReferenceSearchService $referenceSearchService): ConfiguredMessagingSystem
    {
        self::registerAnnotationAutoloader($this->rootPathToSearchConfigurationFor);
        $interfaceToCallRegistry = InterfaceToCallRegistry::createWithInterfaces($this->interfacesToCall, $this->isLazyConfiguration, $referenceSearchService);
        if (!$this->isLazyConfiguration) {
            $this->prepareAndOptimizeConfiguration($interfaceToCallRegistry);
        }

        $converters = [];
        foreach ($this->converterBuilders as $converterBuilder) {
            $converters[] = $converterBuilder->build($referenceSearchService);
        }
        $referenceSearchService = $this->prepareReferenceSearchServiceWithInternalReferences($referenceSearchService, $converters, $interfaceToCallRegistry);
        if ($this->isLazyConfiguration) {
            /** @var ProxyFactory $proxyFactory */
            $proxyFactory = $referenceSearchService->get(ProxyFactory::REFERENCE_NAME);
            $proxyFactory->lockConfiguration();
        }


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
            $this->isLazyConfiguration
        );
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param array $converters
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @return InMemoryReferenceSearchService|ReferenceSearchService
     * @throws MessagingException
     * @throws ReferenceNotFoundException
     */
    private function prepareReferenceSearchServiceWithInternalReferences(ReferenceSearchService $referenceSearchService, array $converters, InterfaceToCallRegistry $interfaceToCallRegistry)
    {
        return InMemoryReferenceSearchService::createWithReferenceService($referenceSearchService,
            array_merge(
                $this->moduleReferenceSearchService->getAllRegisteredReferences(),
                [
                    ConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith($converters),
                    InterfaceToCallRegistry::REFERENCE_NAME => $interfaceToCallRegistry
                ]
            )
        );
    }

    /**
     * Only one instance at time
     *
     * @internal
     */
    private function __clone()
    {

    }

    /**
     * @param string|null $rootPathToSearchConfigurationFor
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    private static function registerAnnotationAutoloader(?string $rootPathToSearchConfigurationFor): void
    {
        if ($rootPathToSearchConfigurationFor) {
            $path = $rootPathToSearchConfigurationFor . '/vendor/autoload.php';
            Assert::isTrue(file_exists($path), "Can't find autoload file on {$path}. Is autoload generated correctly?");
            $loader = require $path;
            AnnotationRegistry::registerLoader(array($loader, "loadClass"));
        }
    }
}