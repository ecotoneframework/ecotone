<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;

interface DistributedBus
{
    public function sendCommand(string $destination, string $routingKey, string $command, string $sourceMediaType = MediaType::TEXT_PLAIN, array $metadata = []) : void;

    /**
     * @param object|array $command
     * @param array        $metadata
     */
    public function convertAndSendCommand(string $destination, string $routingKey, $command, array $metadata = []) : void;

    public function publishEvent(string $routingKey, string $event, string $sourceMediaType = MediaType::TEXT_PLAIN, array $metadata = []) : void;

    /**
     * @param object|array $data
     * @param array  $metadata
     */
    public function convertAndPublishEvent(string $routingKey, $event, array $metadata) : void;
}