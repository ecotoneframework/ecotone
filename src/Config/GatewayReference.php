<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class GatewayReference
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayReference
{
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var object
     */
    private $gateway;

    /**
     * GatewayReference constructor.
     * @param string $referenceName
     * @param object $gateway
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function __construct(string $referenceName, $gateway)
    {
        Assert::isObject($gateway, "Gateway should always be object");

        $this->referenceName = $referenceName;
        $this->gateway = $gateway;
    }

    /**
     * @param GatewayBuilder $gatewayBuilder
     * @param ReferenceSearchService $referenceSearchService
     * @param ChannelResolver $channelResolver
     * @return GatewayReference
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createWith(GatewayBuilder $gatewayBuilder, ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver) : self
    {
        return new self($gatewayBuilder->getReferenceName(), $gatewayBuilder->build($referenceSearchService, $channelResolver));
    }

    /**
     * @param string $referenceName
     * @return bool
     */
    public function hasReferenceName(string $referenceName) : bool
    {
        return $this->referenceName == $referenceName;
    }

    /**
     * @return object
     */
    public function getGateway()
    {
        return $this->gateway;
    }
}