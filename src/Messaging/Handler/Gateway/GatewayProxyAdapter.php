<?php

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Config\NonProxyCombinedGateway;
use ProxyManager\Factory\RemoteObject\AdapterInterface;

class GatewayProxyAdapter implements AdapterInterface
{
    public function __construct(private NonProxyCombinedGateway $nonProxyCombinedGateway)
    {

    }

    /**
     * @inheritDoc
     */
    public function call(string $wrappedClass, string $method, array $params = [])
    {
        return $this->nonProxyCombinedGateway->executeMethod($method, $params);
    }
}
