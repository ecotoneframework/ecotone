<?php

namespace Fixture\Handler;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class DumbGatewayBuilder
 * @package Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DumbGatewayBuilder implements GatewayBuilder
{
    /**
     * @var array
     */
    private $requiredReferences = [];

    private function __construct()
    {
    }

    public function withRequiredReference(string $referenceName) : self
    {
        $this->requiredReferences[] = $referenceName;

        return $this;
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
    public function getRequestChannelName(): string
    {
        // TODO: Implement getInputChannelName() method.
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
        return \stdClass::class;
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver)
    {
        return new \stdClass();
    }

    public function __toString()
    {
        return "dumb gateway";
    }
}