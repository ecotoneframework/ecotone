<?php

namespace Ecotone\Modelling;

/**
 * licence Apache-2.0
 */
interface DistributionEntrypoint
{
    public const DISTRIBUTED_CHANNEL              = 'ecotone.distributed.invoke';
    public const DISTRIBUTED_ROUTING_KEY          = 'ecotone.distributed.routingKey';
    public const DISTRIBUTED_PAYLOAD_TYPE         = 'ecotone.distributed.payloadType';

    public function distribute($payload, array $metadata, string $payloadType, string $routingKey, string $mediaType);

    public function distributeMessage($payload, array $metadata, string $mediaType);
}
