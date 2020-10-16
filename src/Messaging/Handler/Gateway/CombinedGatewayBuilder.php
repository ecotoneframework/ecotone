<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\NonProxyGateway;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ProxyManager\Factory\RemoteObjectFactory;

/**
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CombinedGatewayBuilder
{
    private string $interfaceName;
    /**
     * @var NonProxyGateway[]
     */
    private array $gatewayBuilders;

    /**
     * MultipleMethodGatewayBuilder constructor.
     * @param string $interfaceName
     * @param NonProxyGateway[] $gatewayBuilders
     */
    private function __construct(string $interfaceName, array $gatewayBuilders)
    {
        $this->interfaceName = $interfaceName;
        $this->gatewayBuilders = $gatewayBuilders;
    }

    /**
     * @param string $interfaceName
     * @param NonProxyGateway[] $gatewayBuilders
     * @return CombinedGatewayBuilder
     */
    public static function create(string $interfaceName, array $gatewayBuilders): self
    {
        return new self($interfaceName, $gatewayBuilders);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver): \ProxyManager\Proxy\RemoteObjectInterface
    {
        $gatewaysToPass = $this->gatewayBuilders;
        $factory = new RemoteObjectFactory(new class($gatewaysToPass) implements AdapterInterface
        {
            /**
             * @var NonProxyGateway[]
             */
            private array $gateways;

            /**
             *  constructor.
             *
             * @param NonProxyGateway[] $gateways
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
                if (!isset($this->gateways[$method])) {
                    throw new \InvalidArgumentException("{$wrappedClass}:{$method} has not registered gateway");
                }

                return call_user_func_array([$this->gateways[$method], "execute"], [$params]);
            }
        });

        return $factory->createProxy($this->interfaceName);
    }
}