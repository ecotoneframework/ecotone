<?php

declare(strict_types=1);

namespace Ecotone\Api\Lite;

use Ecotone\AnnotationFinder\FileSystem\FileSystemAnnotationFinder;
use Ecotone\AnnotationFinder\FileSystem\RootCatalogNotFound;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\TestConfiguration;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Messaging\ConfiguredMessagingSystem;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Lite\Test\Configuration\InMemoryRepositoryBuilder;
use Ecotone\Lite\Test\ConfiguredMessagingSystemWithTestSupport;
use Ecotone\Lite\Test\DatabaseTablesSetUp;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleClassList;
use Ecotone\Messaging\InMemoryConfigurationVariableService;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\BaseEventSourcingConfiguration;
use Ecotone\SymfonyContainer\ContainerCacheLayout;
use Ecotone\SymfonyContainer\EcotoneSymfonyContainerFactory;

use function json_decode;

use Psr\Container\ContainerInterface;

/**
 * licence Apache-2.0
 */
final class EcotoneLite
{
    /**
     * @param string[] $classesToResolve
     * @param array<string,mixed> $configurationVariables
     * @param ContainerInterface|object[] $containerOrAvailableServices
     */
    public static function bootstrap(
        array                    $classesToResolve = [],
        ContainerInterface|array $containerOrAvailableServices = [],
        ?ServiceConfiguration    $configuration = null,
        array                    $configurationVariables = [],
        bool                     $useCachedVersion = false,
        ?string                  $pathToRootCatalog = null,
        bool                     $allowGatewaysToBeRegisteredInContainer = false,
        ?string                  $licenceKey = null,
    ): ConfiguredMessagingSystem {
        return self::prepareConfiguration(
            $containerOrAvailableServices,
            $configuration,
            $classesToResolve,
            $configurationVariables,
            $pathToRootCatalog,
            false,
            $allowGatewaysToBeRegisteredInContainer,
            $useCachedVersion,
            $licenceKey,
        );
    }

    /**
     * @param string[] $classesToResolve
     * @param array<string,mixed> $configurationVariables
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
        bool                     $addInMemoryEventSourcedRepository = true,
        ?TestConfiguration        $testConfiguration = null,
        ?string                  $licenceKey = null
    ): FlowTestSupport {
        $configuration = self::prepareForFlowTesting($configuration, $classesToResolve, $addInMemoryStateStoredRepository, $testConfiguration, $licenceKey);

        if ($addInMemoryEventSourcedRepository && ! $configuration->isModulePackageEnabled(ModulePackageList::EVENT_SOURCING_PACKAGE) && ! $configuration->hasExtensionObject(BaseEventSourcingConfiguration::class)) {
            $configuration = $configuration->addExtensionObject(InMemoryRepositoryBuilder::createDefaultEventSourcedRepository());
        }

        return self::withDatabaseTablesSetUp(
            self::prepareConfiguration($containerOrAvailableServices, $configuration, $classesToResolve, $configurationVariables, $pathToRootCatalog, true, $allowGatewaysToBeRegisteredInContainer, false),
            $configuration,
        );
    }

    private static function withDatabaseTablesSetUp(ConfiguredMessagingSystemWithTestSupport $messagingSystem, ServiceConfiguration $configuration): FlowTestSupport
    {
        foreach ($configuration->extensionObjects as $extensionObject) {
            if ($extensionObject instanceof TestConfiguration && $extensionObject->databaseTablesSetUpOnBootstrap) {
                $messagingSystem->getServiceFromContainer(DatabaseTablesSetUp::class)->setUpTablesOfProvidedConnections();
            }
        }

        return $messagingSystem->getFlowTestSupport();
    }

    /**
     * @param ContainerInterface|object[] $containerOrAvailableServices
     * @param string[] $classesToResolve
     * @param array<string,mixed> $configurationVariables
     */
    private static function prepareConfiguration(ContainerInterface|array $containerOrAvailableServices, ?ServiceConfiguration $serviceConfiguration, array $classesToResolve, array $configurationVariables, ?string $originalPathToRootCatalog, bool $enableTesting, bool $allowGatewaysToBeRegisteredInContainer, bool $useCachedVersion, ?string $enterpriseLicenceKey = null): ConfiguredMessagingSystemWithTestSupport|ConfiguredMessagingSystem
    {
        // moving out of vendor catalog
        $pathToRootCatalog = $originalPathToRootCatalog ?: __DIR__ . '/../../../../';
        try {
            $pathToRootCatalog = FileSystemAnnotationFinder::getRealRootCatalog($pathToRootCatalog, $pathToRootCatalog);
        } catch (RootCatalogNotFound $exception) {
            // This will be used when symlinks to Ecotone packages are used (e.g. Split Testing - Github Actions)
            $debug = debug_backtrace();
            $pathToRootCatalog = FileSystemAnnotationFinder::getRealRootCatalog(
                dirname(array_pop($debug)['file']),
                $pathToRootCatalog
            );
        }

        if (is_null($serviceConfiguration)) {
            $serviceConfiguration = ServiceConfiguration::createWithDefaults();
        }

        if ($enterpriseLicenceKey !== null) {
            $serviceConfiguration = $serviceConfiguration->withLicenceKey($enterpriseLicenceKey);
        }

        $externalContainer = $containerOrAvailableServices instanceof ContainerInterface ? $containerOrAvailableServices : InMemoryPSRContainer::createFromAssociativeArray($containerOrAvailableServices);
        $serviceConfiguration = MessagingSystemConfiguration::addCorePackage($serviceConfiguration, $enableTesting);

        $cacheLayout = ContainerCacheLayout::resolve(
            $pathToRootCatalog,
            $serviceConfiguration,
            $serviceConfiguration->cacheDirectoryPath,
            self::shouldUseAutomaticCache($useCachedVersion, $pathToRootCatalog),
            configurationVariables: $configurationVariables,
            classesToResolve: $classesToResolve,
            enableTesting: $enableTesting,
            providedServiceIds: $enableTesting ? self::providedServiceIds($containerOrAvailableServices) : [],
        );
        $annotationFinder = $cacheLayout->annotationFinder;
        $serviceCacheConfiguration = $cacheLayout->serviceCacheConfiguration;
        $cacheHash = $cacheLayout->configHash;

        $configurationVariableService = InMemoryConfigurationVariableService::create($configurationVariables);

        $container = EcotoneSymfonyContainerFactory::bootstrap(
            $serviceCacheConfiguration,
            $configurationVariableService,
            $externalContainer,
            function () use ($annotationFinder, $configurationVariableService, $serviceConfiguration, $enableTesting, $externalContainer) {
                $messagingConfiguration = MessagingSystemConfiguration::prepareWithAnnotationFinder(
                    $annotationFinder,
                    $configurationVariableService,
                    $serviceConfiguration,
                    $enableTesting
                );
                $messagingConfiguration->withExternalContainer($externalContainer);

                return $messagingConfiguration;
            },
            $cacheHash,
        );

        $messagingSystem = $container->get(ConfiguredMessagingSystem::class);

        if ($allowGatewaysToBeRegisteredInContainer) {
            Assert::isTrue(method_exists($externalContainer, 'set'), 'Gateways registration was enabled however given container has no `set` method. Please add it or turn off the option.');
            $container->registerBridgesInto(
                fn (string $referenceName, string $interfaceName, callable $factory) => $externalContainer->set($referenceName, $factory()),
            );
        } elseif ($externalContainer->has(ConfiguredMessagingSystem::class)) {
            /** @var ConfiguredMessagingSystem $alreadyConfiguredMessaging */
            $alreadyConfiguredMessaging = $externalContainer->get(ConfiguredMessagingSystem::class);

            $alreadyConfiguredMessaging->replaceWith($messagingSystem);
        }

        if ($enableTesting) {
            $messagingSystem = new ConfiguredMessagingSystemWithTestSupport($messagingSystem);
        }

        gc_collect_cycles();
        return $messagingSystem;
    }

    private static function refuseTestConfigurationPassedAsExtensionObject(ServiceConfiguration $configuration): void
    {
        if (! $configuration->hasExtensionObject(TestConfiguration::class)) {
            return;
        }

        throw ConfigurationException::create(
            'EcotoneLite::bootstrapFlowTesting() was given a TestConfiguration as an extension object of the ServiceConfiguration. '
            . 'A flow test runs with exactly one TestConfiguration, the one passed as the testConfiguration: parameter, so this one would be replaced by that one and its settings ignored. '
            . 'Remove it from withExtensionObjects() / addExtensionObject() and pass it as EcotoneLite::bootstrapFlowTesting(..., testConfiguration: $yourTestConfiguration) instead.'
        );
    }

    private static function prepareForFlowTesting(
        ?ServiceConfiguration $configuration,
        array                 $classesToResolve,
        bool                  $addInMemoryStateStoredRepository,
        ?TestConfiguration    $testConfiguration,
        ?string               $enterpriseLicenceKey,
    ): ServiceConfiguration {
        $configuration = $configuration ?: ServiceConfiguration::createWithDefaults();
        self::refuseTestConfigurationPassedAsExtensionObject($configuration);
        $testConfiguration ??= TestConfiguration::createWithDefaults();

        if (! $configuration->areEnabledModulePackageNamesDefined()) {
            $configuration = $configuration->withEnabledModulePackageNames(self::installedModulePackageNames());
        }

        $configuration = $configuration
            ->addExtensionObject($testConfiguration);

        if ($addInMemoryStateStoredRepository) {
            $configuration = $configuration
                ->addExtensionObject(InMemoryRepositoryBuilder::createDefaultStateStoredRepository());
        }

        if ($enterpriseLicenceKey !== null) {
            $configuration = $configuration
                ->withLicenceKey($enterpriseLicenceKey);
        }

        return $configuration;
    }

    /**
     * @return string[]
     */
    private static function installedModulePackageNames(): array
    {
        return array_values(array_filter(
            array_diff(ModuleClassList::packageNames(), [ModulePackageList::TEST_PACKAGE]),
            fn (string $packageName): bool => array_filter(
                ModuleClassList::forPackage($packageName),
                fn (string $moduleClassName): bool => class_exists($moduleClassName),
            ) !== [],
        ));
    }

    /**
     * @param ContainerInterface|object[] $containerOrAvailableServices
     * @return string[]|null
     */
    private static function providedServiceIds(ContainerInterface|array $containerOrAvailableServices): ?array
    {
        if ($containerOrAvailableServices instanceof ContainerInterface) {
            return null;
        }

        $serviceIds = [];
        foreach ($containerOrAvailableServices as $referenceName => $service) {
            $serviceIds[] = is_int($referenceName) ? get_class($service) : $referenceName;
        }

        return $serviceIds;
    }

    private static function shouldUseAutomaticCache(bool $useCachedVersion, string $pathToRootCatalog): bool
    {
        $composerPath = $pathToRootCatalog . DIRECTORY_SEPARATOR . 'composer.json';
        if (! $useCachedVersion && file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            if (! isset($composer['name']) || ! self::isRunningTestsForEcotoneFramework($composer['name'])) {
                $useCachedVersion = true;
            }
        } else {
            $useCachedVersion = true;
        }

        return $useCachedVersion;
    }

    private static function isRunningTestsForEcotoneFramework($name): bool
    {
        return str_starts_with($name, 'ecotone');
    }
}
