<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint\InboundChannelAdapter;

/**
 * Interface InboundChannelGateway
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint\InboundChannelAdapter
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