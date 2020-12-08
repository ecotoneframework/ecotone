<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;

interface DistributedCommandBus
{
    public function send(string $destination, string $routingKey, string $command, string $sourceMediaType = MediaType::TEXT_PLAIN, array $metadata = []) : void;

    /**
     * @param object|array $command
     * @param array        $metadata
     */
    public function convertAndSend(string $destination, string $routingKey, $command, array $metadata = []) : void;
}