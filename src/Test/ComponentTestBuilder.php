<?php

namespace Ecotone\Test;

use Ecotone\AnnotationFinder\FileSystem\FileSystemAnnotationFinder;
use Ecotone\Lite\InMemoryContainerImplementation;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Lite\Test\FlowTestSupport;
use Ecotone\Lite\Test\MessagingTestSupport;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Compiler\RegisterInterfaceToCallReferences;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceCacheConfiguration;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\ConfigurationVariableService;
use Ecotone\Messaging\Endpoint\InterceptedChannelAdapterBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptorBuilder;
use Ecotone\Messaging\InMemoryConfigurationVariableService;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;

/**
 * licence Apache-2.0
 */
class ComponentTestBuilder
{
    private function __construct(
        private InMemoryPSRContainer $container,
        private MessagingSystemConfiguration $messagingSystemConfiguration
    ) {
    }

    /**
     * @param array<string, string> $configurationVariables
     */
    public static function create(
        array                 $classesToResolve = [],
        ?ServiceConfiguration $configuration = null,
        array                 $configurationVariables = [],
        bool                  $defaultEnterpriseMode = false,
    ): self {
        // This will be used when symlinks to Ecotone packages are used (e.g. Split Testing - Github Actions)
        $debug = debug_backtrace();
        $path = dirname(array_pop($debug)['file']);
        $pathToRootCatalog = FileSystemAnnotationFinder::getRealRootCatalog($path, $path);

        FileSystemAnnotationFinder::getRealRootCatalog($pathToRootCatalog, $pathToRootCatalog);

        $configurationVariableService = InMemoryConfigurationVariableService::create($configurationVariables);
        $serviceConfiguration = $configuration ?? ServiceConfiguration::createWithDefaults()->withSkippedModulePackageNames(ModulePackageList::allPackages());
        if ($defaultEnterpriseMode) {
            $serviceConfiguration = $serviceConfiguration->withLicenceKey(LicenceTesting::VALID_LICENCE);
        }

        return new self(
            InMemoryPSRContainer::createFromAssociativeArray([
                ServiceCacheConfiguration::REFERENCE_NAME => ServiceCacheConfiguration::noCache(),
                ConfigurationVariableService::REFERENCE_NAME => $configurationVariableService,
            ]),
            MessagingSystemConfiguration::prepare(
                $pathToRootCatalog,
                $configurationVariableService,
                $serviceConfiguration,
                $classesToResolve,
                true,
            )
        );
    }

    public function withChannel(MessageChannelBuilder $channelBuilder): self
    {
        $this->messagingSystemConfiguration->registerMessageChannel($channelBuilder);

        return $this;
    }

    public function withConverter(CompilableBuilder $converter): self
    {
        $this->messagingSystemConfiguration->registerConverter($converter);

        return $this;
    }

    public function withConverters(array $converters): self
    {
        foreach ($converters as $converter) {
            $this->withConverter($converter);
        }

        return $this;
    }

    public function withPollingMetadata(PollingMetadata $pollingMetadata): self
    {
        $this->messagingSystemConfiguration->registerPollingMetadata($pollingMetadata);

        return $this;
    }

    public function withReference(string $referenceName, object $object): self
    {
        $this->container->set($referenceName, $object);

        return $this;
    }

    public function withMessageHandler(MessageHandlerBuilder $messageHandlerBuilder): self
    {
        $this->messagingSystemConfiguration->registerMessageHandler($messageHandlerBuilder);

        return $this;
    }

    public function withInboundChannelAdapter(InterceptedChannelAdapterBuilder $inboundChannelAdapterBuilder): self
    {
        $this->messagingSystemConfiguration->registerConsumer($inboundChannelAdapterBuilder);

        return $this;
    }

    public function withGateway(GatewayProxyBuilder $gatewayProxyBuilder): self
    {
        $this->messagingSystemConfiguration->registerGatewayBuilder($gatewayProxyBuilder);

        return $this;
    }

    public function withAroundInterceptor(AroundInterceptorBuilder $aroundInterceptorBuilder): self
    {
        $this->messagingSystemConfiguration->registerAroundMethodInterceptor($aroundInterceptorBuilder);

        return $this;
    }

    public function withBeforeInterceptor(MethodInterceptorBuilder $methodInterceptorBuilder): self
    {
        $this->messagingSystemConfiguration->registerBeforeMethodInterceptor($methodInterceptorBuilder);

        return $this;
    }

    public function withAfterInterceptor(MethodInterceptorBuilder $methodInterceptorBuilder): self
    {
        $this->messagingSystemConfiguration->registerAfterMethodInterceptor($methodInterceptorBuilder);

        return $this;
    }

    public function build(): FlowTestSupport
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addCompilerPass($this->messagingSystemConfiguration);
        $containerBuilder->addCompilerPass(new RegisterInterfaceToCallReferences());
        $containerBuilder->addCompilerPass(new InMemoryContainerImplementation($this->container));
        $containerBuilder->compile();

        /** @var ConfiguredMessagingSystem $configuredMessagingSystem */
        $configuredMessagingSystem = $this->container->get(ConfiguredMessagingSystem::class);

        return new FlowTestSupport(
            $configuredMessagingSystem->getGatewayByName(CommandBus::class),
            $configuredMessagingSystem->getGatewayByName(EventBus::class),
            $configuredMessagingSystem->getGatewayByName(QueryBus::class),
            $configuredMessagingSystem->getServiceFromContainer(AggregateDefinitionRegistry::class),
            $configuredMessagingSystem->getGatewayByName(MessagingTestSupport::class),
            $configuredMessagingSystem->getGatewayByName(MessagingEntrypoint::class),
            $configuredMessagingSystem
        );
    }

    public function getGatewayByName(string $name)
    {
        return $this->container->get($name);
    }
}
