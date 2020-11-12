<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;

interface EventBus
{
    /**
     * @return mixed
     */
    public function publish(object $event);

    /**
     * @return mixed
     */
    public function publishWithMetadata(object $event, array $metadata);


    /**
     * @return mixed
     * @var mixed $data
     */
    public function publishWithRouting(string $routingKey, $event, string $eventMediaType = MediaType::APPLICATION_X_PHP);

    /**
     * @return mixed
     * @var mixed $data
     */
    public function publishWithRoutingAndMetadata(string $routingKey, $event, string $eventMediaType = MediaType::APPLICATION_X_PHP, array $metadata = []);
}