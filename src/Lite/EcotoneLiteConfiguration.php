<?php

declare(strict_types=1);

namespace Ecotone\Lite;

use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\Logger\EchoLogger;
use Ecotone\Messaging\InMemoryConfigurationVariableService;
use Psr\Container\ContainerInterface;

/**
 * @TODO Ecotone 2.0 drop this class completely and make use of
 * EcotoneLiteApplication
 * Symfony
 * Laravel
 */
/**
 * licence Apache-2.0
 */
class EcotoneLiteConfiguration
{
    /**
     * @deprecated use EcotoneLiteApplication or EcotoneLite instead
     */
    public static function create(string $rootProjectDirectoryPath, ContainerInterface $container): ConfiguredMessagingSystem
    {
        return self::createWithConfiguration($rootProjectDirectoryPath, $container, ServiceConfiguration::createWithDefaults(), [], false);
    }

    /**
     * @deprecated use EcotoneLiteApplication or EcotoneLite instead
     */
    public static function createWithConfiguration(string $rootProjectDirectoryPath, ContainerInterface $container, ServiceConfiguration $serviceConfiguration, array $configurationVariables, bool $useCachedVersion, array $classesToRegister = []): ConfiguredMessagingSystem
    {
        $referenceSearchService = InMemoryReferenceSearchService::createWithContainer($container, ['logger' => new EchoLogger()], $serviceConfiguration);
        return MessagingSystemConfiguration::prepare(
            realpath($rootProjectDirectoryPath),
            InMemoryConfigurationVariableService::create($configurationVariables),
            $serviceConfiguration,
            $classesToRegister
        )->buildMessagingSystemFromConfiguration($referenceSearchService);
    }
}
