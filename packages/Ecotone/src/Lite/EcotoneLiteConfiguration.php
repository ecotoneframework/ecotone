<?php

declare(strict_types=1);

namespace Ecotone\Lite;

use Ecotone\AnnotationFinder\FileSystem\FileSystemAnnotationFinder;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Config\StubConfiguredMessagingSystem;
use Ecotone\Messaging\Handler\Logger\EchoLogger;
use Ecotone\Messaging\InMemoryConfigurationVariableService;
use Psr\Container\ContainerInterface;

/**
 * Class EcotoneLite
 * @package Ecotone\Lite
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EcotoneLiteConfiguration
{
    public static function create(string $rootProjectDirectoryPath, ContainerInterface|GatewayAwareContainer $container): ConfiguredMessagingSystem
    {
        return self::createWithConfiguration($rootProjectDirectoryPath, $container, ServiceConfiguration::createWithDefaults(), [], false);
    }

    public static function createWithConfiguration(string $rootProjectDirectoryPath, ContainerInterface|GatewayAwareContainer $container, ServiceConfiguration $serviceConfiguration, array $configurationVariables, bool $useCachedVersion): ConfiguredMessagingSystem
    {
        $serviceConfiguration = $serviceConfiguration->withNamespaces(array_merge(
            $serviceConfiguration->getNamespaces(),
            [FileSystemAnnotationFinder::FRAMEWORK_NAMESPACE]
        ));

        $configuredMessagingSystem = MessagingSystemConfiguration::prepare(
            realpath($rootProjectDirectoryPath),
            new TypeResolver($container),
            InMemoryConfigurationVariableService::create($configurationVariables),
            $serviceConfiguration,
            $useCachedVersion
        )->buildMessagingSystemFromConfiguration(new PsrContainerReferenceSearchService($container, ['logger' => new EchoLogger(), ConfiguredMessagingSystem::class => new StubConfiguredMessagingSystem()]));

        if ($container instanceof GatewayAwareContainer) {
            foreach ($configuredMessagingSystem->getGatewayList() as $gatewayReference) {
                $container->addGateway($gatewayReference->getReferenceName(), $gatewayReference->getGateway());
            }
        }

        return $configuredMessagingSystem;
    }
}
