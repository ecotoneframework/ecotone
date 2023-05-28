<?php

declare(strict_types=1);

namespace Ecotone\Lite;

use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\Logger\EchoLogger;
use Ecotone\Messaging\InMemoryConfigurationVariableService;
use Psr\Container\ContainerInterface;

/**
 * @TODO Ecotone 2.0 drop this class completely and make use of
 * EcotoneLiteApplication
 * Symfony
 * Laravel
 */
class EcotoneLiteConfiguration
{
    public static function create(string $rootProjectDirectoryPath, ContainerInterface|GatewayAwareContainer $container): ConfiguredMessagingSystem
    {
        return self::createWithConfiguration($rootProjectDirectoryPath, $container, ServiceConfiguration::createWithDefaults(), [], false);
    }

    public static function createWithConfiguration(string $rootProjectDirectoryPath, ContainerInterface|GatewayAwareContainer $container, ServiceConfiguration $serviceConfiguration, array $configurationVariables, bool $useCachedVersion, array $classesToRegister = []): ConfiguredMessagingSystem
    {
        $referenceSearchService = new PsrContainerReferenceSearchService($container, ['logger' => new EchoLogger()]);
        $configuredMessagingSystem = MessagingSystemConfiguration::prepare(
            realpath($rootProjectDirectoryPath),
            InMemoryConfigurationVariableService::create($configurationVariables),
            $serviceConfiguration,
            $useCachedVersion,
            $classesToRegister
        )->buildMessagingSystemFromConfiguration($referenceSearchService);

        $referenceSearchService->setConfiguredMessagingSystem($configuredMessagingSystem);

        if ($container instanceof GatewayAwareContainer) {
            foreach ($configuredMessagingSystem->getGatewayList() as $gatewayReference) {
                $container->addGateway($gatewayReference->getReferenceName(), $gatewayReference->getGateway());
            }
        }

        return $configuredMessagingSystem;
    }
}
