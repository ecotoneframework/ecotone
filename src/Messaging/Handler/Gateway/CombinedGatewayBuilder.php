<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ProxyManager\Factory\RemoteObjectFactory;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class MultipleMethodGatewayBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CombinedGatewayBuilder
{
    /**
     * @var string
     */
    private $interfaceName;
    /**
     * @var GatewayBuilder[]
     */
    private $gatewayBuilders;

    /**
     * MultipleMethodGatewayBuilder constructor.
     * @param string $interfaceName
     * @param GatewayBuilder[] $gatewayBuilders
     */
    private function __construct(string $interfaceName, array $gatewayBuilders)
    {
        $this->interfaceName = $interfaceName;
        $this->gatewayBuilders = $gatewayBuilders;
    }

    /**
     * @param string $interfaceName
     * @param GatewayBuilder[] $gatewayBuilders
     * @return CombinedGatewayBuilder
     */
    public static function create(string $interfaceName, array $gatewayBuilders): self
    {
        return new self($interfaceName, $gatewayBuilders);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver)
    {
        $gateways = [];
        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            $gateways[$gatewayBuilder->getRelatedMethodName()] = $gatewayBuilder->build($referenceSearchService, $channelResolver);
        }

        $factory = new RemoteObjectFactory(new class ($gateways) implements AdapterInterface
        {
            /**
             * @var array
             */
            private $gateways;

            /**
             *  constructor.
             *
             * @param array $gateways
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
                return call_user_func_array([$this->gateways[$method], $method], $params);
            }
        });

        return $factory->createProxy($this->interfaceName);
    }
}