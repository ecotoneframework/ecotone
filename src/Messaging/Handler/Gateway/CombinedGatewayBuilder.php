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
            $buildCallback = function() use ($referenceSearchService, $channelResolver) {
                $gateways = [];
                foreach ($this->gatewayBuilders as $gatewayBuilder) {
                    $gateways[$gatewayBuilder->getRelatedMethodName()] = $gatewayBuilder->buildWithoutProxyObject($referenceSearchService, $channelResolver);
                }

                return $gateways;
            };
        }else {
            $gateways = [];
            foreach ($this->gatewayBuilders as $gatewayBuilder) {
                $gateways[$gatewayBuilder->getRelatedMethodName()] = $gatewayBuilder->buildWithoutProxyObject($referenceSearchService, $channelResolver);
            }

            $buildCallback = function() use ($gateways) {
                return $gateways;
            };
        }


        $factory = new LazyLoadingValueHolderFactory();
        return $factory->createProxy(
            $this->interfaceName,
            function (& $wrappedObject, $proxy, $method, $parameters, & $initializer) use ($buildCallback) {
                $factory = new RemoteObjectFactory(new class ($buildCallback) implements AdapterInterface
                {
                    /**
                     * @var \Closure
                     */
                    private $buildCallback;

                    /**
                     *  constructor.
                     *
                     * @param \Closure $buildCallback
                     */
                    public function __construct(\Closure $buildCallback)
                    {
                        $this->buildCallback = $buildCallback;
                    }

                    /**
                     * @inheritDoc
                     */
                    public function call(string $wrappedClass, string $method, array $params = [])
                    {
                        $buildCallback = $this->buildCallback;
                        $gateways = $buildCallback();

                        return call_user_func_array([$gateways[$method], "execute"], [$params]);
                    }
                });

                $wrappedObject = $factory->createProxy($this->interfaceName);
            }
        );
    }
}