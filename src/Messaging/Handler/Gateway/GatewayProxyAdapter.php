<?php

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Handler\NonProxyGateway;
use InvalidArgumentException;
use ProxyManager\Factory\RemoteObject\AdapterInterface;

class GatewayProxyAdapter implements AdapterInterface
{
    /**
     * @var NonProxyGateway[]
     */
    private array $gateways;

    /**
     *  constructor.
     *
     * @param array<string, NonProxyGateway> $gateways
     */
    public function __construct(array $gateways)
    {
        $this->gateways = $gateways;
    }

    /**
     * @inheritDoc
     */
    public function call(string $wrappedClass, string $method, array $params = [])
    {
        if (! isset($this->gateways[$method])) {
            throw new InvalidArgumentException("{$wrappedClass}:{$method} has not registered gateway");
        }

        return call_user_func_array([$this->gateways[$method], 'execute'], [$params]);
    }
}
