<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter;

/**
 * Interface InboundChannelGateway
 * @package SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
interface InboundChannelGateway
{
    /**
     * @param $payload
     */
    public function execute($payload) : void;
}