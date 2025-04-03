<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\GatewayProxyReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ServiceCacheConfiguration;

use function file_exists;
use function str_replace;

/**
 * Class LazyProxyConfiguration
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class ProxyFactory
{
    public const PROXY_NAMESPACE = 'Ecotone\\__Proxy__';

    private ProxyGenerator $proxyGenerator;

    public function __construct(private ServiceCacheConfiguration $serviceCacheConfiguration)
    {
        $this->proxyGenerator = new ProxyGenerator(self::PROXY_NAMESPACE);
    }

    public static function getGatewayProxyDefinitionFor(GatewayProxyReference $proxyReference): Definition
    {
        return new Definition(self::getFullClassNameFor($proxyReference), [
            new Definition(GatewayProxyReference::class, [
                $proxyReference->getReferenceName(),
                $proxyReference->getInterfaceName(),
            ]),
            new Reference(ConfiguredMessagingSystem::class),
        ], [self::class, 'createProxyInstance']);
    }

    public function createProxyInstance(GatewayProxyReference $proxyReference, ConfiguredMessagingSystem $messagingSystem): object
    {
        $proxyClassName = $this->loadProxyClass($proxyReference);

        return new $proxyClassName($messagingSystem, $proxyReference);
    }

    public function generateCachedProxyFileFor(GatewayProxyReference $proxyReference, bool $overwrite): string
    {
        $file = $this->getFilePathForProxy($proxyReference);
        if ($overwrite || ! file_exists($file)) {
            $code = $this->generateProxyCode($proxyReference);
            $this->dumpFile($file, "<?php\n\n" . $code);
        }
        return $file;
    }

    private function loadProxyClass(GatewayProxyReference $proxyReference): string
    {
        if (! self::isLoaded($proxyReference)) {
            $file = $this->generateCachedProxyFileFor($proxyReference, ! $this->serviceCacheConfiguration->shouldUseCache());
            require $file;
        }

        return self::getFullClassNameFor($proxyReference);
    }

    private static function isLoaded(GatewayProxyReference $proxyReference): bool
    {
        return class_exists(self::getFullClassNameFor($proxyReference), false);
    }

    private function generateProxyCode(GatewayProxyReference $proxyReference): string
    {
        return $this->proxyGenerator->generateProxyFor(
            self::getFullClassNameFor($proxyReference),
            $proxyReference->getInterfaceName()
        );
    }

    private function getFilePathForProxy(GatewayProxyReference $proxyReference): string
    {
        $className = self::getClassNameFor($proxyReference);
        return $this->serviceCacheConfiguration->getPath() . DIRECTORY_SEPARATOR . $className . '.php';
    }

    private static function getClassNameFor(GatewayProxyReference $proxyReference): string
    {
        return str_replace('\\', '_', $proxyReference->getInterfaceName());
    }

    private static function getFullClassNameFor(GatewayProxyReference $proxyReference): string
    {
        return self::PROXY_NAMESPACE . '\\' . self::getClassNameFor($proxyReference);
    }

    private function dumpFile(string $fileName, string $code): void
    {
        // Code adapted from doctrine/orm/src/Proxy/ProxyFactory.php
        $parentDirectory = dirname($fileName);

        // mkdir race condition https://github.com/kalessil/phpinspectionsea/blob/master/docs/probable-bugs.md#mkdir-race-condition
        if (! is_dir($parentDirectory) && ! @mkdir($parentDirectory, 0775, true) && ! is_dir($parentDirectory)) {
            throw ConfigurationException::create("Cannot create cache directory {$parentDirectory}");
        }

        if (! is_writable($parentDirectory)) {
            throw ConfigurationException::create("Cache directory is not writable {$parentDirectory}");
        }

        $tmpFileName = $fileName . '.' . bin2hex(random_bytes(12));

        file_put_contents($tmpFileName, $code);
        @chmod($tmpFileName, 0664);
        rename($tmpFileName, $fileName);
    }
}
