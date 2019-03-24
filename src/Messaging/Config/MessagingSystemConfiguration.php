<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Annotation\WithRequiredReferenceNameList;
use SimplyCodedSoftware\Messaging\Channel\ChannelInterceptorBuilder;
use SimplyCodedSoftware\Messaging\Channel\EventDrivenChannelInterceptorAdapter;
use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Channel\PollableChannelInterceptorAdapter;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Conversion\AutoCollectionConversionService;
use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Conversion\ConverterBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ChannelAdapterConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerEndpointFactory;
use SimplyCodedSoftware\Messaging\Endpoint\MessageHandlerConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class Configuration
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MessagingSystemConfiguration implements Configuration
{
    /**
     * @var MessageChannelBuilder[]
     */
    private $channelBuilders = [];
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
    private $preCallMethodInterceptors = [];
    /**
     * @var AroundInterceptorReference[]
     */
    private $aroundMethodInterceptors = [];
    /**
     * @var MethodInterceptor[]
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
     * @var InterfaceToCall[]
     */
    private $interfacesToCall = [];

    /**
     * Only one instance at time
     *
     * Configuration constructor.
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     * @param object[] $extensionObjects
     * @param ReferenceTypeFromNameResolver $referenceTypeFromNameResolver
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct(ModuleRetrievingService $moduleConfigurationRetrievingService, array $extensionObjects, ReferenceTypeFromNameResolver $referenceTypeFromNameResolver)
    {
        $this->initialize($moduleConfigurationRetrievingService, $extensionObjects, $referenceTypeFromNameResolver);
    }

    /**
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     * @param object[] $extensionObjects
     * @param ReferenceTypeFromNameResolver $referenceTypeFromNameResolver
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function initialize(ModuleRetrievingService $moduleConfigurationRetrievingService, array $extensionObjects, ReferenceTypeFromNameResolver $referenceTypeFromNameResolver): void
    {
        $modules = $moduleConfigurationRetrievingService->findAllModuleConfigurations();
        $moduleExtensions = [];

        foreach ($modules as $module) {
            $this->requireReferences($module->getRequiredReferences());

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
                $moduleExtensions[$module->getName()]
            );
        }
        $interfaceToCallRegistry = InterfaceToCallRegistry::createWith($referenceTypeFromNameResolver);
        $this->configureInterceptors($interfaceToCallRegistry);
        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            $relatedInterfaces = $messageHandlerBuilder->resolveRelatedReferences($interfaceToCallRegistry);

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

            $this->interfacesToCall = array_merge($this->interfacesToCall, $relatedInterfaces);
        }

        $this->interfacesToCall = array_unique($this->interfacesToCall);
    }

    /**
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     * @return Configuration
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function prepare(ModuleRetrievingService $moduleConfigurationRetrievingService): Configuration
    {
        return new self($moduleConfigurationRetrievingService, $moduleConfigurationRetrievingService->findAllExtensionObjects(), InMemoryReferenceTypeFromNameResolver::createEmpty());
    }

    /**
     * @param ModuleRetrievingService $moduleConfigurationRetrievingService
     * @param ReferenceTypeFromNameResolver $referenceTypeFromNameResolver
     * @return Configuration
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function prepareWithCachedReferenceObjects(ModuleRetrievingService $moduleConfigurationRetrievingService, ReferenceTypeFromNameResolver $referenceTypeFromNameResolver): Configuration
    {
        return new self($moduleConfigurationRetrievingService, $moduleConfigurationRetrievingService->findAllExtensionObjects(), $referenceTypeFromNameResolver);
    }

    /**
     * @param PollingMetadata $pollingMetadata
     * @return Configuration
     */
    public function registerPollingMetadata(PollingMetadata $pollingMetadata): Configuration
    {
        $this->messageHandlerPollingMetadata[$pollingMetadata->getEndpointId()] = $pollingMetadata;

        return $this;
    }

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return Configuration
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function registerBeforeMethodInterceptor(MethodInterceptor $methodInterceptor): Configuration
    {
        $this->checkIfInterceptorIsCorrect($methodInterceptor);

        $this->preCallMethodInterceptors[] = $methodInterceptor;
        $this->preCallMethodInterceptors = $this->orderMethodInterceptors($this->preCallMethodInterceptors);
        $this->requireReferences($methodInterceptor->getMessageHandler()->getRequiredReferenceNames());

        return $this;
    }

    /**
     * @param MethodInterceptor $methodInterceptor
     * @return Configuration
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function registerAfterMethodInterceptor(MethodInterceptor $methodInterceptor): Configuration
    {
        $this->checkIfInterceptorIsCorrect($methodInterceptor);

        $this->postCallMethodInterceptors[] = $methodInterceptor;
        $this->postCallMethodInterceptors = $this->orderMethodInterceptors($this->postCallMethodInterceptors);
        $this->requireReferences($methodInterceptor->getMessageHandler()->getRequiredReferenceNames());

        return $this;
    }

    /**
     * @param \SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference $aroundInterceptorReference
     * @return Configuration
     */
    public function registerAroundMethodInterceptor(AroundInterceptorReference $aroundInterceptorReference) : Configuration
    {
        $this->aroundMethodInterceptors[] = $aroundInterceptorReference;
        $this->requireReferences([$aroundInterceptorReference->getInterceptorName()]);

        return $this;
    }

    /**
     * @param MessageHandlerBuilderWithOutputChannel[] $methodInterceptors
     * @return array
     */
    private function orderMethodInterceptors(array $methodInterceptors) : array
    {
        usort($methodInterceptors, function(MethodInterceptor $methodInterceptor, MethodInterceptor $toCompare){
            if ($methodInterceptor->getPrecedence() === $toCompare->getPrecedence()) {
                return 0;
            }

            if ($methodInterceptor->getPrecedence() > $toCompare->getPrecedence()) {
                return -1;
            }

            return 1;
        });

        return $methodInterceptors;
    }

    /**
     * @inheritDoc
     */
    public function registerChannelInterceptor(ChannelInterceptorBuilder $channelInterceptorBuilder): Configuration
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
            if ($requiredReferenceName instanceof RequiredReference) {
                $requiredReferenceName = $requiredReferenceName->getReferenceName();
            }

            if (in_array($requiredReferenceName, [InterfaceToCallRegistry::REFERENCE_NAME, ConversionService::REFERENCE_NAME])) {
                continue;
            }

            if ($requiredReferenceName) {
                $this->requiredReferences[] = $requiredReferenceName;
            }
        }

        $this->requiredReferences = array_unique($this->requiredReferences);
    }

    /**
     * @param MessageHandlerBuilder $messageHandlerBuilder
     * @return Configuration
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function registerMessageHandler(MessageHandlerBuilder $messageHandlerBuilder): Configuration
    {
        Assert::notNullAndEmpty($messageHandlerBuilder->getInputMessageChannelName(), "Lack information about input message channel for {$messageHandlerBuilder}");

        if (is_null($messageHandlerBuilder->getEndpointId()) || $messageHandlerBuilder->getEndpointId() === "") {
            $messageHandlerBuilder->withEndpointId(Uuid::uuid4()->toString());
        }
        if (array_key_exists($messageHandlerBuilder->getEndpointId(), $this->messageHandlerBuilders)) {
            throw ConfigurationException::create("Trying to register endpoints with same id. {$messageHandlerBuilder} and {$this->messageHandlerBuilders[$messageHandlerBuilder->getEndpointId()]}");
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
        $this->registeredGateways[$gatewayBuilder->getReferenceName()] = $gatewayBuilder->getInterfaceName();
        $this->requireReferences($gatewayBuilder->getRequiredReferences());

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
    public function registerConverter(ConverterBuilder $converterBuilder): Configuration
    {
        $this->converterBuilders[] = $converterBuilder;

        return $this;
    }

    /**
     * Initialize messaging system from current configuration
     *
     * @param ReferenceSearchService $referenceSearchService
     * @return ConfiguredMessagingSystem
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function buildMessagingSystemFromConfiguration(ReferenceSearchService $referenceSearchService): ConfiguredMessagingSystem
    {
        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            if (!array_key_exists($messageHandlerBuilder->getInputMessageChannelName(), $this->channelBuilders)) {
                $this->channelBuilders[$messageHandlerBuilder->getInputMessageChannelName()] = SimpleMessageChannelBuilder::createDirectMessageChannel($messageHandlerBuilder->getInputMessageChannelName());
            }
        }


        $interfaceToCallRegistry = InterfaceToCallRegistry::createWithInterfaces($this->interfacesToCall);
        $converters = [];
        foreach ($this->converterBuilders as $converterBuilder) {
            $converters[] = $converterBuilder->build($referenceSearchService);
        }
        $referenceSearchServiceWithExtras = InMemoryReferenceSearchService::createWithReferenceService($referenceSearchService, [
            ConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith($converters),
            InterfaceToCallRegistry::REFERENCE_NAME => $interfaceToCallRegistry
        ]);

        $channelResolver = $this->createChannelResolver($referenceSearchServiceWithExtras);
        $gateways = [];
        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            $gatewayReference = GatewayReference::createWith($gatewayBuilder, $referenceSearchServiceWithExtras, $channelResolver);
            $gateways[] = $gatewayReference;
        }

        $this->configureInterceptors($interfaceToCallRegistry);

        $consumerEndpointFactory = new ConsumerEndpointFactory($channelResolver, $referenceSearchServiceWithExtras, $this->consumerFactories, $this->messageHandlerPollingMetadata);
        $consumers = [];

        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            $consumers[] = $consumerEndpointFactory->createForMessageHandler($messageHandlerBuilder);
        }
        foreach ($this->channelAdapters as $channelAdapter) {
            $consumers[] = $channelAdapter->build($channelResolver, $referenceSearchServiceWithExtras);
        }

        return MessagingSystem::create($consumers, $gateways, $channelResolver);
    }

    /**
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @param MessageHandlerBuilderWithOutputChannel $interceptedMessageHandlerBuilder
     * @return MessageHandlerBuilderWithOutputChannel[]
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function findPreCallInterceptorsFor(InterfaceToCallRegistry $interfaceToCallRegistry, MessageHandlerBuilderWithOutputChannel $interceptedMessageHandlerBuilder) : array
    {
        $preCallInterceptors = [];

        foreach ($this->preCallMethodInterceptors as $preCallInterceptor) {
            if (in_array($preCallInterceptor->getReferenceName(), $interceptedMessageHandlerBuilder->getRequiredInterceptorReferenceNames()) || $preCallInterceptor->doesItCutWith($interfaceToCallRegistry, $interceptedMessageHandlerBuilder)) {
                $preCallInterceptors[] = $preCallInterceptor->getMessageHandler();
            }
        }

        return $preCallInterceptors;
    }

    /**
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @param MessageHandlerBuilderWithOutputChannel $interceptedMessageHandlerBuilder
     * @return MessageHandlerBuilderWithOutputChannel[]
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function findPostCallInterceptorsFor(InterfaceToCallRegistry $interfaceToCallRegistry, MessageHandlerBuilderWithOutputChannel $interceptedMessageHandlerBuilder) : array
    {
        $postCallInterceptors = [];

        foreach ($this->postCallMethodInterceptors as $postCallInterceptor) {
            if (in_array($postCallInterceptor->getReferenceName(), $interceptedMessageHandlerBuilder->getRequiredInterceptorReferenceNames()) || $postCallInterceptor->doesItCutWith($interfaceToCallRegistry, $interceptedMessageHandlerBuilder)) {
                $postCallInterceptors[] = $postCallInterceptor->getMessageHandler();
            }
        }

        return $postCallInterceptors;
    }

    /**
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @param MessageHandlerBuilderWithOutputChannel $interceptedMessageHandlerBuilder
     * @return MessageHandlerBuilderWithOutputChannel[]
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function findAroundInterceptorsFor(InterfaceToCallRegistry $interfaceToCallRegistry, MessageHandlerBuilderWithOutputChannel $interceptedMessageHandlerBuilder) : array
    {
        $aroundCallInterceptors = [];

        foreach ($this->aroundMethodInterceptors as $aroundMethodInterceptor) {
            if (in_array($aroundMethodInterceptor->getInterceptorName(), $interceptedMessageHandlerBuilder->getRequiredInterceptorReferenceNames()) || $aroundMethodInterceptor->doesItCutWith($interfaceToCallRegistry, $interceptedMessageHandlerBuilder)) {
                $aroundCallInterceptors[] = $aroundMethodInterceptor;
            }
        }

        return $aroundCallInterceptors;
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return ChannelResolver
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
        foreach ($this->channelBuilders as $channelsBuilder) {
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
     * @param InterfaceToCallRegistry $interfaceRegistry
     * @return void
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function configureInterceptors(InterfaceToCallRegistry $interfaceRegistry): void
    {
        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            if ($messageHandlerBuilder instanceof MessageHandlerBuilderWithOutputChannel) {
                $preCallInterceptors = $this->findPreCallInterceptorsFor($interfaceRegistry, $messageHandlerBuilder);
                $aroundInterceptors = $this->findAroundInterceptorsFor($interfaceRegistry, $messageHandlerBuilder);
                $postCallInterceptors = $this->findPostCallInterceptorsFor($interfaceRegistry, $messageHandlerBuilder);

                foreach ($aroundInterceptors as $aroundInterceptorReference) {
                    $messageHandlerBuilder->addAroundInterceptor($aroundInterceptorReference);
                }
                if ($preCallInterceptors || $postCallInterceptors) {
                    $messageHandlerBuilderToUse = ChainMessageHandlerBuilder::create()
                        ->withEndpointId($messageHandlerBuilder->getEndpointId())
                        ->withInputChannelName($messageHandlerBuilder->getInputMessageChannelName())
                        ->withOutputMessageChannel($messageHandlerBuilder->getOutputMessageChannelName());

                    foreach ($preCallInterceptors as $preCallInterceptor) {
                        $messageHandlerBuilderToUse->chain($preCallInterceptor);
                    }
                    $messageHandlerBuilderToUse->chain($messageHandlerBuilder);
                    foreach ($postCallInterceptors as $postCallInterceptor) {
                        $messageHandlerBuilderToUse->chain($postCallInterceptor);
                    }

                    $this->messageHandlerBuilders[$messageHandlerBuilder->getEndpointId()] = $messageHandlerBuilderToUse;
                }
            }
        }

        $this->preCallMethodInterceptors = [];
        $this->aroundMethodInterceptors = [];
        $this->postCallMethodInterceptors = [];
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
     * @param MethodInterceptor $methodInterceptor
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
}