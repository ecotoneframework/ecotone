<?php
declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\MediaType;

interface CommandBus
{
    public function send(object $command, array $metadata = []) : mixed;

    public function sendWithRouting(string $routingKey, mixed $command = [], string $commandMediaType = MediaType::APPLICATION_X_PHP, array $metadata = []) : mixed;
}