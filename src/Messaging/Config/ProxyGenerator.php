<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Handler\Gateway\ProxyFactory;
use ProxyManager\Factory\RemoteObjectFactory;
use Psr\Container\ContainerInterface;

final class ProxyGenerator
{
    public static function createFor(string $referenceName, ContainerInterface $container, string $interface, string $cacheDirectoryPath)
    {
        $factory = new RemoteObjectFactory(
            new EcotoneRemoteAdapter($container, $referenceName),
            ProxyFactory::createWithCache($cacheDirectoryPath)->getConfiguration()
        );

        return $factory->createProxy($interface);
    }
}
