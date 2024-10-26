<?php

declare(strict_types=1);

namespace Ecotone\Lite;

use Ecotone\AnnotationFinder\FileSystem\AutoloadFileNamespaceParser;
use Ecotone\AnnotationFinder\FileSystem\FileSystemAnnotationFinder;
use Ecotone\AnnotationFinder\FileSystem\RootCatalogNotFound;
use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\EventSourcing\EventSourcingConfiguration;
use Ecotone\Lite\Test\Configuration\InMemoryRepositoryBuilder;
use Ecotone\Lite\Test\ConfiguredMessagingSystemWithTestSupport;
use Ecotone\Lite\Test\FlowTestSupport;
use Ecotone\Lite\Test\TestConfiguration;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\Container\ContainerConfig;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceCacheConfiguration;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\ConfigurationVariableService;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\InMemoryConfigurationVariableService;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\BaseEventSourcingConfiguration;
use Ecotone\Modelling\Config\RegisterAggregateRepositoryChannels;

use function json_decode;

use Psr\Container\ContainerInterface;
use ReflectionClass;

/**
 * licence Apache-2.0
 */
final class EcotoneLite
{
    /**
     * @param string[] $classesToResolve
     * @param array<string,string> $configurationVariables
     * @param ContainerInterface|object[] $containerOrAvailableServices
     * @param bool $allowGatewaysToBeRegisteredInContainer when enabled will add to the container Command/Query/Event and other gateways. Your container must have 'set' method however
     * @param string|null $licenceKey licence key for enterprise version
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
     * This should be used in cases we want to test stateless services.
     * It will not register any repositories for aggregates.
     *
     * In case you want to test flows or stateful classes like Aggregates and Sagas, use "bootstrapFlowTesting" instead
     *
     * @param string[] $classesToResolve
     * @param array<string,string> $configurationVariables
     * @param ContainerInterface|object[] $containerOrAvailableServices
     * @deprecated Ecotone 2.0 will drop this method, use "bootstrapFlowTesting" instead
     */
    public static function bootstrapForTesting(
        array                    $classesToResolve = [],
        ContainerInterface|array $containerOrAvailableServices = [],
        ?ServiceConfiguration    $configuration = null,
        array                    $configurationVariables = [],
        ?string                  $pathToRootCatalog = null,
        bool                     $allowGatewaysToBeRegisteredInContainer = false
    ): ConfiguredMessagingSystemWithTestSupport {
        if (! $configuration) {
            $configuration = ServiceConfiguration::createWithDefaults();
        }

        if (! $configuration->areSkippedPackagesDefined()) {
            $configuration = $configuration
                ->withSkippedModulePackageNames(ModulePackageList::allPackages());
        }

        return self::prepareConfiguration($containerOrAvailableServices, $configuration, $classesToResolve, $configurationVariables, $pathToRootCatalog, true, $allowGatewaysToBeRegisteredInContainer, false);
    }

    /**
     * Provides default configuration for testing flows
     * Skips all module package names and registers repositories for aggregates
     *
     * @param string[] $classesToResolve
     * @param array<string,string> $configurationVariables
     * @param ContainerInterface|object[] $containerOrAvailableServices
     * @param MessageChannelBuilder[] $enableAsynchronousProcessing
     * @param string|null $licenceKey licence key for enterprise version
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
        ?array                   $enableAsynchronousProcessing = null,
        TestConfiguration        $testConfiguration = null,
        ?string                  $licenceKey = null
    ): FlowTestSupport {
        $configuration = self::prepareForFlowTesting($configuration, ModulePackageList::allPackages(), $classesToResolve, $addInMemoryStateStoredRepository, $enableAsynchronousProcessing, $testConfiguration, $licenceKey);

        if ($addInMemoryEventSourcedRepository) {
            $configuration = $configuration->addExtensionObject(InMemoryRepositoryBuilder::createDefaultEventSourcedRepository());
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
     * @param string|null $licenceKey licence key for enterprise version
     */
    public static function bootstrapFlowTestingWithEventStore(
        array                    $classesToResolve = [],
        ContainerInterface|array $containerOrAvailableServices = [],
        ?ServiceConfiguration    $configuration = null,
        array                    $configurationVariables = [],
        ?string                  $pathToRootCatalog = null,
        bool                     $allowGatewaysToBeRegisteredInContainer = false,
        bool                     $addInMemoryStateStoredRepository = true,
        bool                     $runForProductionEventStore = false,
        ?array                   $enableAsynchronousProcessing = null,
        TestConfiguration        $testConfiguration = null,
        ?string                  $licenceKey = null,
    ): FlowTestSupport {
        $configuration = self::prepareForFlowTesting($configuration, ModulePackageList::allPackagesExcept([ModulePackageList::EVENT_SOURCING_PACKAGE, ModulePackageList::DBAL_PACKAGE, ModulePackageList::JMS_CONVERTER_PACKAGE]), $classesToResolve, $addInMemoryStateStoredRepository, $enableAsynchronousProcessing, $testConfiguration, $licenceKey);

        if (! $configuration->hasExtensionObject(BaseEventSourcingConfiguration::class) && ! $runForProductionEventStore) {
            Assert::isTrue(class_exists(EventSourcingConfiguration::class), 'To use Flow Testing with Event Store you need to add event sourcing module.');

            $configuration = $configuration
                ->addExtensionObject(EventSourcingConfiguration::createInMemory());
        }

        if (! $configuration->hasExtensionObject(DbalConfiguration::class)) {
            $configuration = $configuration
                ->addExtensionObject(DbalConfiguration::createForTesting());
        }

        return self::prepareConfiguration($containerOrAvailableServices, $configuration, $classesToResolve, $configurationVariables, $pathToRootCatalog, true, $allowGatewaysToBeRegisteredInContainer, false)
            ->getFlowTestSupport();
    }

    private static function getFileNameBasedOnConfig(
        string $pathToRootCatalog,
        bool $useCachedVersion,
        array $classesToResolve,
        ServiceConfiguration $serviceConfiguration,
        array $configurationVariables,
        bool $enableTesting
    ): string {
        if ($useCachedVersion) {
            return 'messaging';
        }

        // this is temporary cache based on if files have changed
        // get file contents based on class names, configuration and configuration variables
        $fileSha = '';

        if ($serviceConfiguration->getNamespaces()) {
            $classes = FileSystemAnnotationFinder::getRegisteredClassesForNamespaces($pathToRootCatalog, new AutoloadFileNamespaceParser(), $serviceConfiguration->getNamespaces());

            foreach ($classes as $class) {
                $filePath = (new ReflectionClass($class))->getFileName();
                $fileSha .= sha1_file($filePath);
            }
        }

        if (file_exists($pathToRootCatalog . 'composer.lock')) {
            $fileSha .= sha1_file($pathToRootCatalog . 'composer.lock');
        }

        foreach ($classesToResolve as $class) {
            $filePath = (new ReflectionClass($class))->getFileName();

            if ($filePath) {
                $fileSha .= sha1_file($filePath);
            }
        }

        $fileSha .= sha1(serialize($serviceConfiguration));
        $fileSha .= sha1(serialize($configurationVariables));
        $fileSha .= $enableTesting ? 'true' : 'false';

        return sha1($fileSha);
    }

    /**
     * @param string[] $packagesToEnable
     * @param ContainerInterface|object[] $containerOrAvailableServices
     * @param string[] $classesToResolve
     * @param array<string,string> $configurationVariables
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

        $serviceCacheConfiguration = new ServiceCacheConfiguration(
            $serviceConfiguration->getCacheDirectoryPath(),
            self::shouldUseAutomaticCache($useCachedVersion, $pathToRootCatalog),
        );
        $configurationVariableService = InMemoryConfigurationVariableService::create($configurationVariables);
        $definitionHolder = null;
        $messagingSystemCachePath = null;

        if ($serviceCacheConfiguration->shouldUseCache()) {
            $messagingSystemCachePath = $serviceCacheConfiguration->getPath() . DIRECTORY_SEPARATOR . self::getFileNameBasedOnConfig($pathToRootCatalog, $useCachedVersion, $classesToResolve, $serviceConfiguration, $configurationVariables, $enableTesting);

            if (file_exists($messagingSystemCachePath)) {
                /** It may fail on deserialization, then return `false` and we can build new one */
                $definitionHolder = unserialize(file_get_contents($messagingSystemCachePath));
            }
        }

        if (! $definitionHolder) {
            $messagingConfiguration = MessagingSystemConfiguration::prepare(
                $pathToRootCatalog,
                $configurationVariableService,
                $serviceConfiguration,
                $classesToResolve,
                $enableTesting
            );
            $definitionHolder = ContainerConfig::buildDefinitionHolder($messagingConfiguration);

            if ($serviceCacheConfiguration->shouldUseCache()) {
                Assert::notNull($messagingSystemCachePath, 'Cache path should be defined');

                MessagingSystemConfiguration::prepareCacheDirectory($serviceCacheConfiguration);
                file_put_contents($messagingSystemCachePath, serialize($definitionHolder));
            }
        }

        $container = new LazyInMemoryContainer($definitionHolder->getDefinitions(), $externalContainer);
        $container->set(ServiceCacheConfiguration::class, $serviceCacheConfiguration);
        $container->set(ConfigurationVariableService::REFERENCE_NAME, $configurationVariableService);

        $messagingSystem = $container->get(ConfiguredMessagingSystem::class);

        if ($allowGatewaysToBeRegisteredInContainer) {
            Assert::isTrue(method_exists($externalContainer, 'set'), 'Gateways registration was enabled however given container has no `set` method. Please add it or turn off the option.');
            $externalContainer->set(ConfiguredMessagingSystem::class, $messagingSystem);
            foreach ($messagingSystem->getGatewayList() as $gatewayReference) {
                $gatewayReferenceName = $gatewayReference->getReferenceName();
                $externalContainer->set($gatewayReferenceName, $messagingSystem->getGatewayByName($gatewayReferenceName));
            }
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

    private static function prepareForFlowTesting(
        ?ServiceConfiguration $configuration,
        array                 $packagesToSkip,
        array                 $classesToResolve,
        bool                  $addInMemoryStateStoredRepository,
        ?array                $enableAsynchronousProcessing,
        ?TestConfiguration    $testConfiguration,
        ?string               $enterpriseLicenceKey,
    ): ServiceConfiguration {
        if ($enableAsynchronousProcessing !== null) {
            if ($configuration !== null && in_array(ModulePackageList::ASYNCHRONOUS_PACKAGE, $configuration->getSkippedModulesPackages())) {
                Assert::isFalse($configuration->areSkippedPackagesDefined(), 'If you use `enableAsynchronousProcessing` configuration, you can\'t use `skippedPackages` amd skip Asynchronous Package. Please allows asynchronous package.');
            }
            Assert::isTrue($enableAsynchronousProcessing !== [], 'For enabled asynchronous processing you must provide Message Channel');
        }
        if ($enableAsynchronousProcessing) {
            $packagesToSkip = array_diff($packagesToSkip, [ModulePackageList::ASYNCHRONOUS_PACKAGE]);
        }

        $configuration = $configuration ?: ServiceConfiguration::createWithDefaults();
        $testConfiguration ??= TestConfiguration::createWithDefaults();

        if (! $configuration->areSkippedPackagesDefined()) {
            $configuration = $configuration
                ->withSkippedModulePackageNames($packagesToSkip);
        }

        if ($enableAsynchronousProcessing !== null) {
            foreach ($enableAsynchronousProcessing as $channelBuilder) {
                Assert::isTrue($channelBuilder instanceof MessageChannelBuilder, 'You can only provide MessageChannelBuilder as asynchronous processing channel, under `enableAsynchronousProcessing`');
                $configuration = $configuration->addExtensionObject($channelBuilder);
            }
        }

        $aggregateAnnotation = TypeDescriptor::create(Aggregate::class);
        foreach ($classesToResolve as $class) {
            Assert::isTrue(is_string($class), 'Classes to resolve must be strings, instead given: ' . TypeDescriptor::createFromVariable($class)->toString());
            $aggregateClass = ClassDefinition::createFor(TypeDescriptor::create($class));
            if (! $aggregateClass->hasClassAnnotation($aggregateAnnotation)) {
                continue;
            }

            $configuration = $configuration->addExtensionObject(new RegisterAggregateRepositoryChannels($aggregateClass->getClassType()->toString(), $aggregateClass->getSingleClassAnnotation($aggregateAnnotation) instanceof EventSourcingAggregate));
        }

        $configuration = $configuration
            ->withExtensionObjects(self::getExtensionObjectsWithoutTestConfiguration($configuration))
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
