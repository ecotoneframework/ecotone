<?php
declare(strict_types=1);


namespace Ecotone\Lite;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\AnnotationFinder\FileSystem\FileSystemAnnotationFinder;
use Ecotone\Messaging\Config\Annotation\FileSystemAnnotationRegistrationService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ReferenceTypeFromNameResolver;
use Ecotone\Messaging\Config\StubConfiguredMessagingSystem;
use Ecotone\Messaging\Handler\Gateway\ProxyFactory;
use Ecotone\Messaging\Handler\Logger\EchoLogger;
use Ecotone\Messaging\InMemoryConfigurationVariableService;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionException;

/**
 * Class EcotoneLite
 * @package Ecotone\Lite
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EcotoneLiteConfiguration
{
    public static function create(string $rootProjectDirectoryPath, ContainerInterface $container) : ConfiguredMessagingSystem
    {
        return self::createWithConfiguration($rootProjectDirectoryPath, $container, ServiceConfiguration::createWithDefaults(), []);
    }

    public static function createWithConfiguration(string $rootProjectDirectoryPath, ContainerInterface $container, ServiceConfiguration $applicationConfiguration, array $configurationVariables): ConfiguredMessagingSystem
    {
        $applicationConfiguration = $applicationConfiguration->withNamespaces(array_merge(
            $applicationConfiguration->getNamespaces(),
            [FileSystemAnnotationFinder::FRAMEWORK_NAMESPACE]
        ));

        return MessagingSystemConfiguration::prepare(
            realpath($rootProjectDirectoryPath),
            new TypeResolver($container),
            InMemoryConfigurationVariableService::create($configurationVariables),
            $applicationConfiguration
        )->buildMessagingSystemFromConfiguration(new PsrContainerReferenceSearchService($container, ["logger" => new EchoLogger(), ConfiguredMessagingSystem::class => new StubConfiguredMessagingSystem()]));
    }
}