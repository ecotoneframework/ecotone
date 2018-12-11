<?php

namespace SimplyCodedSoftware\Messaging\Config;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class GatewayReference
 * @package SimplyCodedSoftware\Messaging\Config
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWith(GatewayBuilder $gatewayBuilder, ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver) : self
    {
        return new self($gatewayBuilder->getReferenceName(), $gatewayBuilder->build($referenceSearchService, $channelResolver));
    }

    /**
     * @return string
     */
    public function getReferenceName() : string
    {
        return $this->referenceName;
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