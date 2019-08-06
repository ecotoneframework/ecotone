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
use ProxyManager\Version;
use stdClass;

/**
 * Class LazyProxyConfiguration
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ProxyFactory
{
    const REFERENCE_NAME = "gatewayProxyConfiguration";

    /**
     * @var Configuration
     */
    private $configuration;
    /**
     * @var bool
     */
    private $isLocked = false;

    /**
     * ProxyConfiguration constructor.
     * @param Configuration $configuration
     */
    private function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param string $cacheDirectoryPath
     * @return ProxyFactory
     */
    public static function createWithCache(string $cacheDirectoryPath) : self
    {
        $configuration = new Configuration();
        $configuration->setProxiesTargetDir($cacheDirectoryPath);
        $fileLocator = new FileLocator($configuration->getProxiesTargetDir());
        $configuration->setGeneratorStrategy(new FileWriterGeneratorStrategy($fileLocator));

        return new self($configuration);
    }

    /**
     * @return ProxyFactory
     */
    public static function createNoCache() : self
    {
        return new self(new Configuration());
    }

    /**
     * @return ProxyFactory
     */
    public function lockConfiguration(): self
    {
        $self = clone $this;
        $self->isLocked = true;

        return $self;
    }

    /**
     * @param string[] $classes
     */
    public function warmUpCacheFor(array $classes): void
    {
        foreach ($classes as $className) {
            $factory = new LazyLoadingValueHolderFactory($this->configuration);
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
            }, $this->configuration);

            $factory->createProxy($className);
        }
    }

    /**
     * @param string $interfaceName
     * @param Closure $buildCallback
     * @return object
     * @throws MessagingException
     */
    public function createProxyClass(string $interfaceName, Closure $buildCallback): object
    {
        if ($this->isLocked) {
            spl_autoload_register($this->configuration->getProxyAutoloader());

            $proxyClassName = $this
                ->configuration
                ->getClassNameInflector()
                ->getProxyClassName($interfaceName, [
                    'className' => $interfaceName,
                    'factory' => RemoteObjectFactory::class,
                    'proxyManagerVersion' => Version::getVersion(),
                ]);

            if (!class_exists($proxyClassName)) {
                throw ConfigurationException::create("There is problem with configuration. Proxy class for {$interfaceName} was not pregenerated. Can't use lazy loading configuration.");
            }
        }

        $factory = new LazyLoadingValueHolderFactory($this->configuration);
        return $factory->createProxy(
            $interfaceName,
            function (& $wrappedObject, $proxy, $method, $parameters, & $initializer) use ($interfaceName, $buildCallback) {
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
                }, $this->configuration);

                $wrappedObject = $factory->createProxy($interfaceName);

                return true;
            }
        );
    }
}