<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\NonProxyGateway;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Support\Assert;

class NonProxyCombinedGateway
{
    /**
     * @param array<string, GatewayProxyBuilder|NonProxyGateway> $methodGateways
     */
    private function __construct(
        private string $referenceName,
        private string $interfaceName,
        private array $methodGateways,
        private ReferenceSearchService $referenceSearchService,
        private ChannelResolver $channelResolver
    ) {
    }

    /**
     * @param array<string, GatewayProxyBuilder> $methodGateways
     */
    public static function createWith(
        string $referenceName,
        string $interfaceName,
        array $methodGateways,
        ReferenceSearchService $referenceSearchService,
        ChannelResolver $channelResolver
    ): self {
        return new self($referenceName, $interfaceName, $methodGateways, $referenceSearchService, $channelResolver);
    }

    /**
     * @return string
     */
    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    public function getInterfaceName(): string
    {
        return $this->interfaceName;
    }

    /**
     * @param string $referenceName
     * @return bool
     */
    public function hasReferenceName(string $referenceName): bool
    {
        return $this->referenceName == $referenceName;
    }

    public function executeMethod(string $methodName, array $params)
    {
        Assert::keyExists($this->methodGateways, $methodName, "Can't call gateway {$this->referenceName} with method {$methodName}. The method does not exists");

        if ($this->methodGateways[$methodName] instanceof GatewayProxyBuilder) {
            $this->methodGateways[$methodName] = $this->methodGateways[$methodName]->buildWithoutProxyObject($this->referenceSearchService, $this->channelResolver);
        }

        return $this->methodGateways[$methodName]->execute($params);
    }

    public function getMethodGateways(): array
    {
        return $this->methodGateways;
    }
}
