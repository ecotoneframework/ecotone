<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use ProxyManager\Factory\RemoteObject\AdapterInterface;
use ProxyManager\Factory\RemoteObjectFactory;
use ReflectionClass;
use ReflectionException;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class MultipleMethodGatewayBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CombinedGatewayBuilder implements GatewayBuilder
{
    /**
     * @var string
     */
    private $interfaceName;
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var array|CombinedGatewayDefinition[]
     */
    private $gatewayDefinitions;
    /**
     * @var string[]
     */
    private $requiredReferences = [];

    /**
     * MultipleMethodGatewayBuilder constructor.
     * @param string $referenceName
     * @param string $interfaceName
     * @param CombinedGatewayDefinition[] $combinedGatewayDefinitions
     * @throws ReflectionException
     * @throws MessagingException
     */
    private function __construct(string $referenceName, string $interfaceName, array $combinedGatewayDefinitions)
    {
        $this->referenceName = $referenceName;
        $this->interfaceName = $interfaceName;
        $this->gatewayDefinitions = $combinedGatewayDefinitions;

        foreach ($combinedGatewayDefinitions as $gatewayBuilder) {
            if (!(new ReflectionClass($interfaceName))->hasMethod($gatewayBuilder->getRelatedMethod())) {
                throw InvalidArgumentException::create("Combined gateway has no method {$gatewayBuilder->getRelatedMethod()}");
            }

            $this->requiredReferences = array_merge($this->requiredReferences, $gatewayBuilder->getGatewayBuilder()->getRequiredReferences());
        }
    }

    /**
     * @param string $referenceName
     * @param string $interfaceName
     * @param CombinedGatewayDefinition[] $gatewayBuilders
     * @return CombinedGatewayBuilder
     */
    public static function create(string $referenceName, string $interfaceName, array $gatewayBuilders): self
    {
        return new self($referenceName, $interfaceName, $gatewayBuilders);
    }

    /**
     * @inheritDoc
     */
    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return $this->requiredReferences;
    }

    /**
     * @inheritDoc
     */
    public function getInterfaceName(): string
    {
        return $this->interfaceName;
    }


    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver)
    {
        $gateways = [];
        foreach ($this->gatewayDefinitions as $gatewayDefinition) {
            $gateways[$gatewayDefinition->getRelatedMethod()] = $gatewayDefinition->getGatewayBuilder()->build($referenceSearchService, $channelResolver);
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