<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\Container\GatewayProxyReference;
use Ecotone\Messaging\Config\EcotoneRemoteAdapter;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ServiceCacheConfiguration;
use ProxyManager\Autoloader\AutoloaderInterface;
use ProxyManager\Configuration;
use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ProxyManager\Factory\RemoteObjectFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use ProxyManager\Proxy\RemoteObjectInterface;
use ProxyManager\Signature\ClassSignatureGenerator;
use ProxyManager\Signature\SignatureGenerator;

use function spl_autoload_register;
use function spl_autoload_unregister;

/**
 * Class LazyProxyConfiguration
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ProxyFactory
{
    private static ?AutoloaderInterface $registeredAutoloader = null;
    private ?Configuration $configuration = null;

    public function __construct(private ServiceCacheConfiguration $serviceCacheConfiguration)
    {
    }

    private function getConfiguration(): Configuration
    {
        return $this->configuration ??= $this->buildConfiguration();
    }

    private function buildConfiguration(): Configuration
    {
        $configuration = new Configuration();

        if ($this->serviceCacheConfiguration->shouldUseCache()) {
            MessagingSystemConfiguration::prepareCacheDirectory($this->serviceCacheConfiguration);
            $configuration->setProxiesTargetDir($this->serviceCacheConfiguration->getPath());
            $fileLocator = new FileLocator($configuration->getProxiesTargetDir());
            $configuration->setGeneratorStrategy(new FileWriterGeneratorStrategy($fileLocator));
            $configuration->setClassSignatureGenerator(new ClassSignatureGenerator(new SignatureGenerator()));
        }

        return $configuration;
    }

    private function createProxyClassWithAdapter(string $interfaceName, AdapterInterface $adapter): RemoteObjectInterface
    {
        $factory = new RemoteObjectFactory($adapter, $this->getConfiguration());
        $this->registerProxyAutoloader();

        return $factory->createProxy($interfaceName);
    }

    public static function createFor(string $referenceName, ConfiguredMessagingSystem $messagingSystem, string $interface, ServiceCacheConfiguration $serviceCacheConfiguration): object
    {
        $proxyFactory = new self($serviceCacheConfiguration);

        return $proxyFactory->createProxyClassWithAdapter(
            $interface,
            new EcotoneRemoteAdapter($messagingSystem, new GatewayProxyReference($referenceName, $interface))
        );
    }

    public function createWithCurrentConfiguration(string $referenceName, ConfiguredMessagingSystem $messagingSystem, string $interface): object
    {
        return self::createFor($referenceName, $messagingSystem, $interface, $this->serviceCacheConfiguration);
    }

    public function registerProxyAutoloader(): void
    {
        if (! $this->serviceCacheConfiguration->shouldUseCache()) {
            return;
        }

        $autoloader = $this->getConfiguration()->getProxyAutoloader();

        if (self::$registeredAutoloader === $autoloader) {
            return;
        }

        if (self::$registeredAutoloader !== null) {
            // another ProxyFactory instance may have already registered an autoloader.
            // this should not happen normally, but just in case we will unload
            // the old autoloader.
            spl_autoload_unregister(self::$registeredAutoloader);
        }

        self::$registeredAutoloader = $autoloader;
        spl_autoload_register(self::$registeredAutoloader);
    }
}
