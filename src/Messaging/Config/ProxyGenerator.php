<?php

namespace Ecotone\Messaging\Config;

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
            public function __construct(private ContainerInterface $container, private string $referenceName)
            {
            }

            /**
             * @inheritDoc
             */
            public function call(string $wrappedClass, string $method, array $params = [])
            {
                /** @var MessagingSystem $messagingSystem */
                $messagingSystem = $this->container->get(ConfiguredMessagingSystem::class);

                $nonProxyCombinedGateway = $messagingSystem->getNonProxyGatewayByName($this->referenceName);

                return $nonProxyCombinedGateway->executeMethod($method, $params);
            }
        }, $proxyFactory->getConfiguration());

        return $factory->createProxy($interface);
    }
}
