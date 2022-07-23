<?php

namespace Ecotone\Modelling;

interface DistributionEntrypoint
{
    public const DISTRIBUTED_CHANNEL              = 'ecotone.distributed.invoke';
    public const DISTRIBUTED_ROUTING_KEY          = 'ecotone.distributed.routingKey';
    public const DISTRIBUTED_PAYLOAD_TYPE         = 'ecotone.distributed.payloadType';

    public function distribute($payload, array $metadata, string $payloadType, string $routingKey, string $mediaType);
}
