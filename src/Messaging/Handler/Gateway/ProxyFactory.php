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

    /**
     * @var Configuration
     */
    private $configuration;
    /**
     * @var string|null
     */
    private $cacheDirectoryPath;

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
                public function call(string $wrappedClass, string $method, array $params = [])
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
     * @return object
     */
    public function createProxyClass(string $interfaceName, Closure $buildCallback): object
    {
        $factory = new RemoteObjectFactory(new class ($buildCallback) implements AdapterInterface
        {
            /**
             * @var Closure
             */
            private $buildCallback;

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
    public function serialize()
    {
        return serialize(["path" => $this->cacheDirectoryPath]);
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $serializedProxy = unserialize($serialized);
        $path  = $serializedProxy['path'];
        if (is_null($path)) {
            $cache = self::createNoCache();
        }else {
            $cache = self::createWithCache($path);
        }

        $this->cacheDirectoryPath = $cache->cacheDirectoryPath;
    }
}