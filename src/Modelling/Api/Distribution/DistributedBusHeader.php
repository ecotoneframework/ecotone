<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Api\Distribution;

/**
 * licence Enterprise
 */
interface DistributedBusHeader
{
    public const DISTRIBUTED_TARGET_SERVICE_NAME = 'ecotone.distributed.targetServiceName';
    public const DISTRIBUTED_SOURCE_SERVICE_NAME = 'ecotone.distributed.sourceServiceName';
    public const DISTRIBUTED_ROUTING_KEY = 'ecotone.distributed.routingKey';
    public const DISTRIBUTED_PAYLOAD_TYPE = 'ecotone.distributed.payloadType';
    public const DISTRIBUTED_ROUTING_SLIP_VALUE = 'ecotone.distributed.invoke';
}
