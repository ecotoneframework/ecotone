<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;

interface DistributedEventBus
{
    public function publish(string $routingKey, string $event, string $sourceMediaType = MediaType::TEXT_PLAIN, array $metadata = []) : void;

    /**
     * @param object|array $data
     * @param array  $metadata
     */
    public function convertAndPublish(string $routingKey, $event, array $metadata) : void;
}