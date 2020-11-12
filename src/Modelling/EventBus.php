<?php

namespace Ecotone\Modelling;

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
    public function publishWithRouting(string $routingKey, string $eventMediaType, $event);

    /**
     * @return mixed
     * @var mixed $data
     */
    public function publishWithRoutingAndMetadata(string $routingKey, string $eventMediaType, $event, array $metadata);
}