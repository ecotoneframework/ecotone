<?php

namespace Ecotone\Messaging\Config;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;

/**
 * Class GatewayReference
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayReference
{
    private string $referenceName;
    private object $gateway;

    /**
     * GatewayReference constructor.
     * @param string $referenceName
     * @param object $gateway
     */
    private function __construct(string $referenceName, object $gateway)
    {
        $this->referenceName = $referenceName;
        $this->gateway = $gateway;
    }

    /**
     * @param string $referenceName
     * @param object $gateway
     * @return GatewayReference
     */
    public static function createWith(string $referenceName, object $gateway) : self
    {
        return new self($referenceName, $gateway);
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

    public function getGateway(): object
    {
        return $this->gateway;
    }
}