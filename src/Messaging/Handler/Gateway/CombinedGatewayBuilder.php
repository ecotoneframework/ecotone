<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ProxyManager\Factory\RemoteObjectFactory;

/**
 * Class MultipleMethodGatewayBuilder
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CombinedGatewayBuilder
{
    /**
     * @var string
     */
    private $interfaceName;
    /**
     * @var GatewayProxyBuilder[]
     */
    private $gatewayBuilders;
    /**
     * @var bool
     */
    private $withLazyBuild = false;

    /**
     * MultipleMethodGatewayBuilder constructor.
     * @param string $interfaceName
     * @param GatewayProxyBuilder[] $gatewayBuilders
     */
    private function __construct(string $interfaceName, array $gatewayBuilders)
    {
        $this->interfaceName = $interfaceName;
        $this->gatewayBuilders = $gatewayBuilders;
    }

    /**
     * @param string $interfaceName
     * @param GatewayProxyBuilder[] $gatewayBuilders
     * @return CombinedGatewayBuilder
     */
    public static function create(string $interfaceName, array $gatewayBuilders): self
    {
        return new self($interfaceName, $gatewayBuilders);
    }

    /**
     * @param bool $withLazyBuild
     * @return CombinedGatewayBuilder
     */
    public function withLazyBuild(bool $withLazyBuild) : self
    {
        $this->withLazyBuild = $withLazyBuild;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver)
    {
        if ($this->withLazyBuild) {
            $gateways = [];
            foreach ($this->gatewayBuilders as $gatewayBuilder) {
                $gateways[$gatewayBuilder->getRelatedMethodName()] = function() use ($gatewayBuilder, $referenceSearchService, $channelResolver) {
                    return $gatewayBuilder->buildWithoutProxyObject($referenceSearchService, $channelResolver);
                };
            }
        }else {
            $gateways = [];
            foreach ($this->gatewayBuilders as $gatewayBuilder) {
                $builtGateway = $gatewayBuilder->buildWithoutProxyObject($referenceSearchService, $channelResolver);
                $gateways[$gatewayBuilder->getRelatedMethodName()] = function() use ($builtGateway) {
                    return $builtGateway;
                };
            }
        }


        $factory = new LazyLoadingValueHolderFactory();
        return $factory->createProxy(
            $this->interfaceName,
            function (& $wrappedObject, $proxy, $method, $parameters, & $initializer) use ($gateways) {
                $factory = new RemoteObjectFactory(new class ($gateways) implements AdapterInterface
                {
                    /**
                     * @var \Closure[]
                     */
                    private $buildCallbacks;
                    /**
                     * @var object[]
                     */
                    private $builtGateways;

                    /**
                     *  constructor.
                     *
                     * @param \Closure[] $buildCallbacks
                     */
                    public function __construct(array $buildCallbacks)
                    {
                        $this->buildCallbacks = $buildCallbacks;
                    }

                    /**
                     * @inheritDoc
                     */
                    public function call(string $wrappedClass, string $method, array $params = [])
                    {
                        if (!isset($this->buildCallbacks[$method])) {
                            throw new \InvalidArgumentException("{$wrappedClass}:{$method} has not registered gateway");
                        }

                        if (!isset($this->builtGateways[$method])) {
                            $buildCallback = $this->buildCallbacks[$method];
                            $this->builtGateways[$method] = $buildCallback();
                        }

                        return call_user_func_array([$this->builtGateways[$method], "execute"], [$params]);
                    }
                });

                $wrappedObject = $factory->createProxy($this->interfaceName);

                return true;
            }
        );
    }
}