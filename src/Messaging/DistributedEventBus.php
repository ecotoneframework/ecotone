<?php


namespace Ecotone\Messaging;


use Ecotone\Messaging\Conversion\MediaType;

interface DistributedEventBus
{
    public function publish(string $messageType, string $data, string $sourceMediaType = MediaType::TEXT_PLAIN) : void;

    public function publishWithMetadata(string $messageType, string $sourceMediaType = MediaType::TEXT_PLAIN, array $metadata = []) : void;

    /**
     * @param object|array $data
     */
    public function convertAndPublish(string $messageType, $data) : void;

    /**
     * @param object|array $data
     * @param array  $metadata
     */
    public function convertAndPublishWithMetadata(string $messageType, $data, array $metadata) : void;
}