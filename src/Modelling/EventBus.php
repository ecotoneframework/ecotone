<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;

/**
 * licence Apache-2.0
 */
interface EventBus
{
    public function publish(object $event, array $metadata = []): void;

    public function publishWithRouting(string $routingKey, mixed $event = [], string $eventMediaType = MediaType::APPLICATION_X_PHP, array $metadata = []): void;
}
