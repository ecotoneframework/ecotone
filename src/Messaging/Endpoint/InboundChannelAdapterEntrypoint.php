<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

/**
 * Interface PollingConsumerGatewayEntrypoint
 * @package Ecotone\Messaging\Endpoint\PollingConsumer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface InboundChannelAdapterEntrypoint
{
    public function executeEntrypoint($data): void;
}
