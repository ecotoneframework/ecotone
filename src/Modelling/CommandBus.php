<?php
declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;

interface CommandBus
{
    /**
     * @return mixed
     */
    public function send(
        object $command
    );

    /**
     * @return mixed
     */
    public function sendWithMetadata(
        object $command, array $metadata
    );

    /**
     * @return mixed
     * @var mixed $command
     */
    public function sendWithRouting(
        string $routingKey, $command, string $commandMediaType = MediaType::APPLICATION_X_PHP
    );

    /**
     * @return mixed
     * @var mixed $command
     */
    public function sendWithRoutingAndMetadata(
        string $routingKey, $command, string $commandMediaType = MediaType::APPLICATION_X_PHP, array $metadata = []
    );
}