<?php

namespace Fixture\Handler;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;

/**
 * Class DumbGatewayBuilder
 * @package Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DumbGatewayBuilder implements GatewayBuilder
{
    private function __construct()
    {
    }

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getReferenceName(): string
    {
        return 'dumb';
    }

    /**
     * @inheritDoc
     */
    public function getInputChannelName(): string
    {
        // TODO: Implement getInputChannelName() method.
    }

    /**
     * @inheritDoc
     */
    public function getInterfaceName(): string
    {
        return \stdClass::class;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver)
    {
        return new \stdClass();
    }

    public function __toString()
    {
        return "dumb gateway";
    }
}