<?php
declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;

interface CommandBus
{
    /**
     * @return mixed
     */
    public function send(object $command, array $metadata);

    /**
     * @return mixed
     * @var mixed $command
     */
    public function sendWithRouting(
        string $routingKey, $command, string $commandMediaType = MediaType::APPLICATION_X_PHP, array $metadata = []
    );
}