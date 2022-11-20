<?php

declare(strict_types=1);

namespace Ecotone\Lite;

use Ecotone\Lite\Test\ConfiguredMessagingSystemWithTestSupport;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\InMemoryReferenceTypeFromNameResolver;
use Ecotone\Messaging\Config\MessagingSystem;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ProxyGenerator;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Config\StubConfiguredMessagingSystem;
use Ecotone\Messaging\Handler\Logger\EchoLogger;
use Ecotone\Messaging\InMemoryConfigurationVariableService;
use Ecotone\Messaging\Support\Assert;
use Psr\Container\ContainerInterface;

final class EcotoneLite
{
    public const CONFIGURED_MESSAGING_SYSTEM = ConfiguredMessagingSystem::class;

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
        bool $useCachedVersion = false,
        ?string                  $pathToRootCatalog = null,
        bool $allowGatewaysToBeRegisteredInContainer = false
    ): ConfiguredMessagingSystem {
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
        bool $allowGatewaysToBeRegisteredInContainer = false
    ): ConfiguredMessagingSystemWithTestSupport {
        return self::prepareConfiguration($containerOrAvailableServices, $configuration, $classesToResolve, $configurationVariables, $pathToRootCatalog, true, $allowGatewaysToBeRegisteredInContainer, false);
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
            $container->set(self::CONFIGURED_MESSAGING_SYSTEM, $messagingSystem);
        }elseif ($container->has(self::CONFIGURED_MESSAGING_SYSTEM)) {
            /** @var MessagingSystem $alreadyConfiguredMessaging */
            $alreadyConfiguredMessaging = $container->get(self::CONFIGURED_MESSAGING_SYSTEM);

            $alreadyConfiguredMessaging->replaceWith($messagingSystem);
        }

        if ($enableTesting) {
            $messagingSystem = new ConfiguredMessagingSystemWithTestSupport($messagingSystem);
        }

        return $messagingSystem;
    }
}
