<?php

namespace Ecotone\Modelling;

interface DistributionEntrypoint
{
    const DISTRIBUTED_CHANNEL              = "ecotone.distributed.invoke";
    const DISTRIBUTED_ROUTING_KEY          = "ecotone.distributed.routingKey";
    const DISTRIBUTED_PAYLOAD_TYPE         = "ecotone.distributed.payloadType";

    public function distribute($payload, array $metadata, string $payloadType, string $routingKey, string $mediaType);
}