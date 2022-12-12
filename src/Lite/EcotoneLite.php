<?php

declare(strict_types=1);

namespace Ecotone\Lite;

use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\EventSourcing\EventSourcingConfiguration;
use Ecotone\Lite\Test\Configuration\InMemoryRepositoryBuilder;
use Ecotone\Lite\Test\ConfiguredMessagingSystemWithTestSupport;
use Ecotone\Lite\Test\FlowTestSupport;
use Ecotone\Lite\Test\TestConfiguration;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\InMemoryReferenceTypeFromNameResolver;
use Ecotone\Messaging\Config\MessagingSystem;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ProxyGenerator;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Config\StubConfiguredMessagingSystem;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Logger\EchoLogger;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\InMemoryConfigurationVariableService;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\BaseEventSourcingConfiguration;
use Psr\Container\ContainerInterface;

final class EcotoneLite
{
    /**
     * @param string[] $classesToResolve
     * @param array<string,string> $configurationVariables
     * @param ContainerInterface|object[] $containerOrAvailableServices
     * @param bool $allowGatewaysToBeRegisteredInContainer when enabled will add to the container Command/Query/Event and other gateways. Your container must have 'set' method however
     */
    public static function bootstrap(
        array                    $classesToResolve = [],
        ContainerInterface|array $containerOrAvailableServices = [],
        ?ServiceConfiguration    $configuration = null,
        array                    $configurationVariables = [],
        bool                     $useCachedVersion = false,
        ?string                  $pathToRootCatalog = null,
        bool                     $allowGatewaysToBeRegisteredInContainer = false
    ): ConfiguredMessagingSystem
    {
        return self::prepareConfiguration($containerOrAvailableServices, $configuration, $classesToResolve, $configurationVariables, $pathToRootCatalog, false, $allowGatewaysToBeRegisteredInContainer, $useCachedVersion);
    }

    /**
     * @param string[] $classesToResolve
     * @param array<string,string> $configurationVariables
     * @param ContainerInterface|object[] $containerOrAvailableServices
     */
    public static function bootstrapForTesting(
        array                    $classesToResolve = [],
        ContainerInterface|array $containerOrAvailableServices = [],
        ?ServiceConfiguration    $configuration = null,
        array                    $configurationVariables = [],
        ?string                  $pathToRootCatalog = null,
        bool                     $allowGatewaysToBeRegisteredInContainer = false
    ): ConfiguredMessagingSystemWithTestSupport
    {
        return self::prepareConfiguration($containerOrAvailableServices, $configuration, $classesToResolve, $configurationVariables, $pathToRootCatalog, true, $allowGatewaysToBeRegisteredInContainer, false);
    }

    /**
     * Provides default configuration for testing flows
     * Skips all module package names and registers repositories for aggregates
     *
     * @param string[] $classesToResolve
     * @param array<string,string> $configurationVariables
     * @param ContainerInterface|object[] $containerOrAvailableServices
     */
    public static function bootstrapFlowTesting(
        array                    $classesToResolve = [],
        ContainerInterface|array $containerOrAvailableServices = [],
        ?ServiceConfiguration    $configuration = null,
        array                    $configurationVariables = [],
        ?string                  $pathToRootCatalog = null,
        bool                     $allowGatewaysToBeRegisteredInContainer = false,
        bool                     $addInMemoryStateStoredRepository = true,
        bool                     $addEventSourcedRepository = true,
    ): FlowTestSupport
    {
        $configuration = self::prepareForFlowTesting($configuration, ModulePackageList::allPackages(), $classesToResolve, $addInMemoryStateStoredRepository);

        if ($addEventSourcedRepository) {
            $configuration = $configuration
                ->addExtensionObject(InMemoryRepositoryBuilder::createForAllEventSourcedAggregates());
        }

        return self::prepareConfiguration($containerOrAvailableServices, $configuration, $classesToResolve, $configurationVariables, $pathToRootCatalog, true, $allowGatewaysToBeRegisteredInContainer, false)
            ->getFlowTestSupport();
    }

    /**
     * Provides default configuration for testing flows with In Memory Event Store.
     * Enables eventSourcing, dbal, jmsConverter packages and provides default repositories.
     *
     * @param string[] $classesToResolve
     * @param array<string,string> $configurationVariables
     * @param ContainerInterface|object[] $containerOrAvailableServices
     */
    public static function bootstrapFlowTestingWithEventStore(
        array                    $classesToResolve = [],
        ContainerInterface|array $containerOrAvailableServices = [],
        ?ServiceConfiguration    $configuration = null,
        array                    $configurationVariables = [],
        ?string                  $pathToRootCatalog = null,
        bool                     $allowGatewaysToBeRegisteredInContainer = false,
        bool                     $addInMemoryStateStoredRepository = true,
    ): FlowTestSupport
    {
        $configuration = self::prepareForFlowTesting($configuration, ModulePackageList::allPackagesExcept([ModulePackageList::EVENT_SOURCING_PACKAGE, ModulePackageList::DBAL_PACKAGE, ModulePackageList::JMS_CONVERTER_PACKAGE]), $classesToResolve, $addInMemoryStateStoredRepository);

        if (!$configuration->hasExtensionObject(BaseEventSourcingConfiguration::class)) {
            Assert::isTrue(class_exists(EventSourcingConfiguration::class), "To use Flow Testing with Event Store you need to add event sourcing module.");

            $configuration = $configuration
                ->addExtensionObject(EventSourcingConfiguration::createInMemory());
        }

        if (!$configuration->hasExtensionObject(DbalConfiguration::class)) {
            $configuration = $configuration
                ->addExtensionObject(DbalConfiguration::createForTesting());
        }

        return self::prepareConfiguration($containerOrAvailableServices, $configuration, $classesToResolve, $configurationVariables, $pathToRootCatalog, true, $allowGatewaysToBeRegisteredInContainer, false)
            ->getFlowTestSupport();
    }

    /**
     * @param string[] $packagesToEnable
     * @param GatewayAwareContainer|object[] $containerOrAvailableServices
     * @param string[] $classesToResolve
     * @param array<string,string> $configurationVariables
     */
    private static function prepareConfiguration(ContainerInterface|array $containerOrAvailableServices, ?ServiceConfiguration $serviceConfiguration, array $classesToResolve, array $configurationVariables, ?string $pathToRootCatalog, bool $enableTesting, bool $allowGatewaysToBeRegisteredInContainer, bool $useCachedVersion): ConfiguredMessagingSystemWithTestSupport|ConfiguredMessagingSystem
    {
        //        moving out of vendor catalog
        $pathToRootCatalog = $pathToRootCatalog ?: __DIR__ . '/../../../../';
        if (is_null($serviceConfiguration)) {
            $serviceConfiguration = ServiceConfiguration::createWithDefaults();
        }

        $container = $containerOrAvailableServices instanceof ContainerInterface ? $containerOrAvailableServices : InMemoryPSRContainer::createFromAssociativeArray($containerOrAvailableServices);

        $messagingConfiguration = MessagingSystemConfiguration::prepare(
            $pathToRootCatalog,
            InMemoryReferenceTypeFromNameResolver::createFromReferenceSearchService(new PsrContainerReferenceSearchService($container)),
            InMemoryConfigurationVariableService::create($configurationVariables),
            $serviceConfiguration,
            $useCachedVersion,
            $classesToResolve,
            $enableTesting
        );

        if ($allowGatewaysToBeRegisteredInContainer) {
            Assert::isTrue(method_exists($container, 'set'), 'Gateways registration was enabled however given container has no `set` method. Please add it or turn off the option.');

            foreach ($messagingConfiguration->getRegisteredGateways() as $gatewayProxyBuilder) {
                $container->set($gatewayProxyBuilder->getReferenceName(), ProxyGenerator::createFor(
                    $gatewayProxyBuilder->getReferenceName(),
                    $container,
                    $gatewayProxyBuilder->getInterfaceName(),
                    $serviceConfiguration->getCacheDirectoryPath() ?: sys_get_temp_dir()
                ));
            }
        }

        $messagingSystem = $messagingConfiguration->buildMessagingSystemFromConfiguration(
            new PsrContainerReferenceSearchService($container, ['logger' => new EchoLogger(), ConfiguredMessagingSystem::class => new StubConfiguredMessagingSystem()])
        );

        if ($allowGatewaysToBeRegisteredInContainer) {
            $container->set(ConfiguredMessagingSystem::class, $messagingSystem);
        } elseif ($container->has(ConfiguredMessagingSystem::class)) {
            /** @var MessagingSystem $alreadyConfiguredMessaging */
            $alreadyConfiguredMessaging = $container->get(ConfiguredMessagingSystem::class);

            $alreadyConfiguredMessaging->replaceWith($messagingSystem);
        }

        if ($enableTesting) {
            $messagingSystem = new ConfiguredMessagingSystemWithTestSupport($messagingSystem);
        }

        return $messagingSystem;
    }

    private static function getExtensionObjectsWithoutTestConfiguration(ServiceConfiguration $configuration): array
    {
        $extensionObjectsWithoutTestConfiguration = [];
        foreach ($configuration->getExtensionObjects() as $extensionObject) {
            if ($extensionObject instanceof TestConfiguration) {
                continue;
            }

            $extensionObjectsWithoutTestConfiguration[] = $extensionObject;
        }

        return $extensionObjectsWithoutTestConfiguration;
    }

    private static function prepareForFlowTesting(?ServiceConfiguration $configuration, array $packagesToSkip, array $classesToResolve, bool $addInMemoryStateStoredRepository): ServiceConfiguration
    {
        $configuration = $configuration ?: ServiceConfiguration::createWithDefaults();
        $testConfiguration = ExtensionObjectResolver::resolveUnique(TestConfiguration::class, $configuration->getExtensionObjects(), TestConfiguration::createWithDefaults());

        if (!$configuration->areSkippedPackagesDefined()) {
            $configuration = $configuration
                ->withSkippedModulePackageNames($packagesToSkip);
        }

        $aggregate = TypeDescriptor::create(Aggregate::class);
        foreach ($classesToResolve as $class) {
            if (ClassDefinition::createFor(TypeDescriptor::create($class))->hasClassAnnotation($aggregate)) {
                $testConfiguration = $testConfiguration->addAggregateUnderTest($class);
            }
        }

        $configuration = $configuration
            ->withExtensionObjects(self::getExtensionObjectsWithoutTestConfiguration($configuration))
            ->addExtensionObject($testConfiguration);

        if ($addInMemoryStateStoredRepository) {
            $configuration = $configuration
                ->addExtensionObject(InMemoryRepositoryBuilder::createForAllStateStoredAggregates());
        }
        return $configuration;
    }
}
