<?php

namespace Ecotone\Modelling;

interface QueryBus
{
    /**
     * @return mixed
     */
    public function send(
        object $query
    );

    /**
     * @return mixed
     */
    public function sendWithMetadata(
        object $query, array $metadata
    );

    /**
     * @return mixed
     * @var mixed $query
     */
    public function sendWithRouting(string $routingKey, string $queryMediaType, $query);

    /**
     * @return mixed
     * @var mixed $query
     */
    public function sendWithRoutingAndMetadata(string $routingKey, string $queryMediaType, $query, array $metadata);
}