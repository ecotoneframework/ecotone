<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;

/**
 * licence Apache-2.0
 */
interface QueryBus
{
    public function send(object $query, array $metadata = [], ?string $expectedReturnedMediaType = null): mixed;

    public function sendWithRouting(string $routingKey, mixed $query = [], string $queryMediaType = MediaType::APPLICATION_X_PHP, array $metadata = [], ?string $expectedReturnedMediaType = null): mixed;
}
