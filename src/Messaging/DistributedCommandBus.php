<?php


namespace Ecotone\Messaging;


use Ecotone\Messaging\Conversion\MediaType;

interface DistributedCommandBus
{
    public function send(string $destination, string $messageType, string $data, string $sourceMediaType = MediaType::TEXT_PLAIN) : void;

    public function sendWithMetadata(string $destination, string $messageType, string $sourceMediaType = MediaType::TEXT_PLAIN, array $metadata = []) : void;

    /**
     * @param object|array $data
     */
    public function convertAndSend(string $destination, string $messageType, $data) : void;

    /**
     * @param object|array $data
     * @param array  $metadata
     */
    public function convertAndSendWithMetadata(string $destination, string $messageType, $data, array $metadata) : void;
}