<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;

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
    public function sendWithRouting(string $routingKey, $query, string $queryMediaType = MediaType::APPLICATION_X_PHP);

    /**
     * @return mixed
     * @var mixed $query
     */
    public function sendWithRoutingAndMetadata(string $routingKey, $query, string $queryMediaType = MediaType::APPLICATION_X_PHP, array $metadata = []);
}