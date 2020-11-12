<?php
declare(strict_types=1);

namespace Ecotone\Modelling;

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
        string $routingKey, string $commandMediaType, $command
    );

    /**
     * @return mixed
     * @var mixed $command
     */
    public function sendWithRoutingAndMetadata(
        string $routingKey, string $commandMediaType, $command, array $metadata
    );
}