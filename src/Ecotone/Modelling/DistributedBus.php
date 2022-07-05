<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;

interface DistributedBus
{
    public function sendCommand(string $destination, string $routingKey, string $command, string $sourceMediaType = MediaType::TEXT_PLAIN, array $metadata = []) : void;

    public function convertAndSendCommand(string $destination, string $routingKey, object|array $command, array $metadata = []) : void;

    public function publishEvent(string $routingKey, string $event, string $sourceMediaType = MediaType::TEXT_PLAIN, array $metadata = []) : void;

    public function convertAndPublishEvent(string $routingKey, object|array $event, array $metadata) : void;
}