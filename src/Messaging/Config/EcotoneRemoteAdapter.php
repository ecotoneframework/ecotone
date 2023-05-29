<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use ProxyManager\Factory\RemoteObject\AdapterInterface;
use Psr\Container\ContainerInterface;

class EcotoneRemoteAdapter implements AdapterInterface
{
    public function __construct(private ContainerInterface $container, private string $referenceName)
    {
    }

    public function call(string $wrappedClass, string $method, array $params = [])
    {
        /** @var MessagingSystem $messagingSystem */
        $messagingSystem = $this->container->get(ConfiguredMessagingSystem::class);

        $nonProxyCombinedGateway = $messagingSystem->getNonProxyGatewayByName($this->referenceName);

        return $nonProxyCombinedGateway->executeMethod($method, $params);
    }
}
