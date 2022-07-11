<?php

namespace Ecotone\SymfonyBundle\DepedencyInjection\Compiler;

use Ecotone\Messaging\Config\MessagingSystem;
use Ecotone\Messaging\Handler\Gateway\ProxyFactory;
use Ecotone\SymfonyBundle\EcotoneSymfonyBundle;
use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ProxyManager\Factory\RemoteObjectFactory;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class ProxyGenerator
 * @package App\MessagingBundle\DependencyInjection
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ProxyGenerator
{
    public static function createFor(string $referenceName, Container $container, string $interface, string $cacheDirectoryPath, bool $isLazyLoaded)
    {
        $proxyFactory = ProxyFactory::createWithCache($cacheDirectoryPath);
        $factory = new RemoteObjectFactory(new class ($container, $referenceName) implements AdapterInterface {
            /**
             * @var Container
             */
            private $container;
            /**
             * @var string
             */
            private $referenceName;

            /**
             *  constructor.
             *
             * @param Container $container
             * @param string $referenceName
             */
            public function __construct(Container $container, string $referenceName)
            {
                $this->container = $container;
                $this->referenceName = $referenceName;
            }

            /**
             * @inheritDoc
             */
            public function call(string $wrappedClass, string $method, array $params = [])
            {
                /** @var MessagingSystem $messagingSystem */
                $messagingSystem = $this->container->get(EcotoneSymfonyBundle::CONFIGURED_MESSAGING_SYSTEM);

                $nonProxyCombinedGateway = $messagingSystem->getNonProxyGatewayByName($this->referenceName);

                return $nonProxyCombinedGateway->executeMethod($method, $params);
            }
        }, $proxyFactory->getConfiguration());

        return $factory->createProxy($interface);
    }
}
