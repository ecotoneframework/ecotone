<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Http;
use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class InboundHttpGateway
 * @package SimplyCodedSoftware\Http
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InboundHttpGatewayBuilder implements GatewayBuilder
{
    /**
     * @inheritDoc
     */
    public function getReferenceName(): string
    {
        return Uuid::uuid4()->toString();
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getInterfaceName(): string
    {
        return InboundHttpGateway::class;
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver)
    {
        // TODO: Implement build() method.
    }
}