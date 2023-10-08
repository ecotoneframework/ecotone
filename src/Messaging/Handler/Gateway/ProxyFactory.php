<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Config\EcotoneRemoteAdapter;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ServiceCacheConfiguration;
use ProxyManager\Configuration;
use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ProxyManager\Factory\RemoteObjectFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use ProxyManager\Proxy\RemoteObjectInterface;
use ProxyManager\Signature\ClassSignatureGenerator;
use ProxyManager\Signature\SignatureGenerator;
use Psr\Container\ContainerInterface;

/**
 * Class LazyProxyConfiguration
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ProxyFactory
{
    public const REFERENCE_NAME = 'gatewayProxyConfiguration';

    private function __construct(private ServiceCacheConfiguration $serviceCacheConfiguration)
    {
    }

    public static function createWithCache(ServiceCacheConfiguration $serviceCacheConfiguration): self
    {
        return new self($serviceCacheConfiguration);
    }

    /**
     * @return Configuration
     */
    public function getConfiguration(): Configuration
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

    public function createProxyClassWithAdapter(string $interfaceName, AdapterInterface $adapter): RemoteObjectInterface
    {
        $factory = new RemoteObjectFactory($adapter, $this->getConfiguration());

        return $factory->createProxy($interfaceName);
    }

    public static function createFor(string $referenceName, ContainerInterface $container, string $interface, ServiceCacheConfiguration $serviceCacheConfiguration): object
    {
        $proxyFactory = self::createWithCache($serviceCacheConfiguration);

        return $proxyFactory->createProxyClassWithAdapter(
            $interface,
            new EcotoneRemoteAdapter($container, $referenceName)
        );
    }

    public function createWithCurrentConfiguration(string $referenceName, ContainerInterface $container, string $interface): object
    {
        return $this->createProxyClassWithAdapter(
            $interface,
            new EcotoneRemoteAdapter($container, $referenceName)
        );
    }
}
