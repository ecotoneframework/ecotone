<?php

namespace Ecotone\Lite;

use Ecotone\Messaging\Config\MessagingSystem;
use Ecotone\Messaging\Handler\Gateway\ProxyFactory;
use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ProxyManager\Factory\RemoteObjectFactory;
use Psr\Container\ContainerInterface;

final class ProxyGenerator
{
    public static function createFor(string $referenceName, ContainerInterface $container, string $interface, string $cacheDirectoryPath)
    {
        $proxyFactory = ProxyFactory::createWithCache($cacheDirectoryPath);
        $factory = new RemoteObjectFactory(new class ($container, $referenceName) implements AdapterInterface {
            private ContainerInterface $container;
            private string $referenceName;

            public function __construct(ContainerInterface $container, string $referenceName)
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
                $messagingSystem = $this->container->get(EcotoneTesting::CONFIGURED_MESSAGING_SYSTEM);

                $nonProxyCombinedGateway = $messagingSystem->getNonProxyGatewayByName($this->referenceName);

                return $nonProxyCombinedGateway->executeMethod($method, $params);
            }
        }, $proxyFactory->getConfiguration());

        return $factory->createProxy($interface);
    }
}
