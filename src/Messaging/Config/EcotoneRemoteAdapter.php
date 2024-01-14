<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Config\Container\GatewayProxyReference;
use Ecotone\Messaging\Handler\Gateway\Gateway;
use ProxyManager\Factory\RemoteObject\AdapterInterface;

class EcotoneRemoteAdapter implements AdapterInterface
{
    public function __construct(private ConfiguredMessagingSystem $messagingSystem, private GatewayProxyReference $gatewayProxyReference)
    {
    }

    public function call(string $wrappedClass, string $method, array $params = []): mixed
    {
        /** @var Gateway $gateway */
        $gateway = $this->messagingSystem->getNonProxyGatewayByName($this->gatewayProxyReference->gatewayReferenceForMethod($method));
        return $gateway->execute($params);
    }
}
