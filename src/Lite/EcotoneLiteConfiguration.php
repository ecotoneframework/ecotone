<?php


namespace Ecotone\Lite;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Messaging\Config\Annotation\FileSystemAnnotationRegistrationService;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Handler\Gateway\ProxyFactory;
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
    private const MESSAGING_SYSTEM_CONFIGURATION_FILE = "messaging_system";

    /**
     * @param string $rootProjectDirectoryPath
     * @param string $cacheDirectoryPath
     * @param ContainerInterface $container
     * @return ConfiguredMessagingSystem
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public static function createWithDefaults(string $rootProjectDirectoryPath, string $cacheDirectoryPath, ContainerInterface $container): ConfiguredMessagingSystem
    {
        return self::createWithCache($rootProjectDirectoryPath, $cacheDirectoryPath, $container, [], true, true, "prod");
    }

    /**
     * @param string $rootProjectDirectoryPath path to root catalog, where composer.json is stored
     * @param string $cacheDirectoryPath where to store application cache
     * @param array $namespaces namespaces which should be loaded, if all exists in src catalog, can be left for $loadSrc=true
     * @param ContainerInterface $container container containing all available services
     * @param bool $loadSrc should load all namespaces from src folder
     * @param bool $failFastStrategy should build all endpoints at the start. Will
     * @param string $environment environment in which application does work
     * @return ConfiguredMessagingSystem
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public static function createWithCache(string $rootProjectDirectoryPath, string $cacheDirectoryPath, ContainerInterface $container, array $namespaces, bool $loadSrc, bool $failFastStrategy, string $environment): ConfiguredMessagingSystem
    {
        $messagingSystemCachePath = $cacheDirectoryPath . DIRECTORY_SEPARATOR . self::MESSAGING_SYSTEM_CONFIGURATION_FILE;

        if (!file_exists($messagingSystemCachePath)) {
            @mkdir($cacheDirectoryPath, 0777, true);
            Assert::isTrue(is_writable($cacheDirectoryPath), "Not enough permissions to write into cache directory {$cacheDirectoryPath}");
            Assert::isFalse(is_file($cacheDirectoryPath), "Cache directory is file, should be directory");

            $messagingSystemConfiguration = self::create($rootProjectDirectoryPath, $cacheDirectoryPath, $container, $namespaces, $failFastStrategy, $loadSrc, $environment);

            $serializedMessagingSystemConfiguration = serialize($messagingSystemConfiguration);
            file_put_contents($messagingSystemCachePath, $serializedMessagingSystemConfiguration);
        } else {
            $messagingSystemConfiguration = unserialize(file_get_contents($messagingSystemCachePath));
        }

        return $messagingSystemConfiguration->buildMessagingSystemFromConfiguration(new PsrContainerReferenceSearchService($container));
    }

    /**
     * @param string $rootProjectDirectoryPath
     * @param string|null $cacheDirectoryPath
     * @param array $namespaces
     * @param ContainerInterface $container
     * @param bool $failFastStrategy
     * @param bool $loadSrc
     * @param string $environment
     * @return MessagingSystemConfiguration
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    private static function create(string $rootProjectDirectoryPath, ?string $cacheDirectoryPath, ContainerInterface $container, array $namespaces, bool $failFastStrategy, bool $loadSrc, string $environment): MessagingSystemConfiguration
    {
        $namespaces = array_merge(
            $namespaces,
            [FileSystemAnnotationRegistrationService::FRAMEWORK_NAMESPACE]
        );

        return MessagingSystemConfiguration::createWithCachedReferenceObjectsForNamespaces(
            realpath($rootProjectDirectoryPath),
            $namespaces,
            new TypeResolver($container),
            $environment,
            $failFastStrategy,
            $loadSrc,
            $cacheDirectoryPath ? ProxyFactory::createWithCache($cacheDirectoryPath) : ProxyFactory::createNoCache()
        );
    }

    /**
     * @param string $cacheDirectoryPath
     */
    public static function cleanCache(string $cacheDirectoryPath): void
    {
        @rmdir($cacheDirectoryPath);
    }
}