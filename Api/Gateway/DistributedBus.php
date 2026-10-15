<?php

namespace Ecotone\Api\Gateway;

use Ecotone\Api\ExtensionObject\MediaType;

/**
 * licence Apache-2.0
 */
interface DistributedBus
{
    public function sendCommand(string $targetServiceName, string $routingKey, string $command, string $sourceMediaType = MediaType::TEXT_PLAIN, array $metadata = []): void;

    public function convertAndSendCommand(string $targetServiceName, string $routingKey, object|array $command, array $metadata = []): void;

    public function publishEvent(string $routingKey, string $event, string $sourceMediaType = MediaType::TEXT_PLAIN, array $metadata = []): void;

    public function convertAndPublishEvent(string $routingKey, object|array $event, array $metadata = []): void;

    public function sendMessage(string $targetServiceName, string $targetChannelName, string $payload, string $sourceMediaType = MediaType::TEXT_PLAIN, array $metadata = []): void;
}
