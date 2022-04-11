<?php

namespace Ecotone\Modelling;

class DistributedMetadata
{
    public static function unsetDistributionKeys(array $metadata): array
    {
        unset($metadata[DistributionEntrypoint::DISTRIBUTED_CHANNEL]);
        unset($metadata[DistributionEntrypoint::DISTRIBUTED_PAYLOAD_TYPE]);
        unset($metadata[DistributionEntrypoint::DISTRIBUTED_ROUTING_KEY]);

        return $metadata;
    }
}