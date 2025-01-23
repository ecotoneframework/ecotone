<?php

namespace Ecotone\Modelling\MessageHandling\Distribution;

/**
 * licence Apache-2.0
 */
interface DistributionEntrypoint
{
    public function distribute($payload, array $metadata, string $payloadType, string $routingKey, string $mediaType);

    public function distributeMessage($payload, array $metadata, string $mediaType);
}
