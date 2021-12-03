<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Handler\Gateway;

use Closure;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\MessagingException;
use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ProxyManager\Factory\RemoteObjectFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use ProxyManager\Signature\ClassSignatureGenerator;
use ProxyManager\Signature\SignatureGenerator;
use ProxyManager\Version;
use stdClass;

/**
 * Class LazyProxyConfiguration
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ProxyFactory implements \Serializable
{
    const REFERENCE_NAME = "gatewayProxyConfiguration";

    private ?string $cacheDirectoryPath;

    /**
     * ProxyConfiguration constructor.
     * @param string|null $cacheDirectoryPath
     */
    private function __construct(?string $cacheDirectoryPath)
    {
        $this->cacheDirectoryPath = $cacheDirectoryPath;
    }

    /**
     * @param string $cacheDirectoryPath
     * @return ProxyFactory
     */
    public static function createWithCache(string $cacheDirectoryPath) : self
    {
        return new self($cacheDirectoryPath);
    }

    /**
     * @return ProxyFactory
     */
    public static function createNoCache() : self
    {
        return new self(null);
    }

    /**
     * @return Configuration
     */
    public function getConfiguration() : Configuration
    {
        $configuration = new Configuration();

        if ($this->cacheDirectoryPath) {
            $configuration->setProxiesTargetDir($this->cacheDirectoryPath);
            $fileLocator = new FileLocator($configuration->getProxiesTargetDir());
            $configuration->setGeneratorStrategy(new FileWriterGeneratorStrategy($fileLocator));
            $configuration->setClassSignatureGenerator(new ClassSignatureGenerator(new SignatureGenerator()));
        }

        return $configuration;
    }

    /**
     * @param string[] $classes
     */
    public function warmUpCacheFor(array $classes): void
    {
        if (!$classes) {
            return;
        }

        foreach ($classes as $className) {
            $factory = new LazyLoadingValueHolderFactory($this->getConfiguration());
            $factory->createProxy(
                $className,
                function (& $wrappedObject, $proxy, $method, $parameters, & $initializer) {
                    $wrappedObject = new stdClass();

                    return true;
                }
            );

            $factory = new RemoteObjectFactory(new class () implements AdapterInterface
            {
                /**
                 * @inheritDoc
                 */
                public function call(string $wrappedClass, string $method, array $params = []): int
                {
                    return 0;
                }
            }, $this->getConfiguration());

            $factory->createProxy($className);
        }
    }

    /**
     * @param string $interfaceName
     * @param Closure $buildCallback
     */
    public function createProxyClass(string $interfaceName, Closure $buildCallback): \ProxyManager\Proxy\RemoteObjectInterface
    {
        $factory = new RemoteObjectFactory(new class ($buildCallback) implements AdapterInterface
        {
            private \Closure $buildCallback;

            /**
             *  constructor.
             *
             * @param Closure $buildCallback
             */
            public function __construct(Closure $buildCallback)
            {
                $this->buildCallback = $buildCallback;
            }

            /**
             * @inheritDoc
             */
            public function call(string $wrappedClass, string $method, array $params = [])
            {
                $buildCallback = $this->buildCallback;
                $gateway = $buildCallback();

                return $gateway->execute($params);
            }
        }, $this->getConfiguration());

        return $factory->createProxy($interfaceName);
    }

    /**
     * @inheritDoc
     */
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function __serialize(): array
    {
        return ["path" => $this->cacheDirectoryPath];
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized): void
    {
        $this->__unserialize(unserialize($serialized));
    }

    public function __unserialize(array $data): void
    {
        $path  = $data['path'];
        if (is_null($path)) {
            $cache = self::createNoCache();
        }else {
            $cache = self::createWithCache($path);
        }

        $this->cacheDirectoryPath = $cache->cacheDirectoryPath;
    }
}