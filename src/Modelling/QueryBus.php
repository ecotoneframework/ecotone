<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;

interface QueryBus
{
    public function send(object $query, array $metadata = []) : mixed;

    public function sendWithRouting(string $routingKey, mixed $query, string $queryMediaType = MediaType::APPLICATION_X_PHP, array $metadata = []) : mixed;
}