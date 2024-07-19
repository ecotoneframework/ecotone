<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

/**
 * Interface PollingConsumerGatewayEntrypoint
 * @package Ecotone\Messaging\Endpoint\PollingConsumer
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface InboundGatewayEntrypoint
{
    public function executeEntrypoint($data);
}
