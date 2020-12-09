<?php


namespace Ecotone\Modelling;


use Ecotone\Messaging\Conversion\MediaType;

class StorageCommandBus implements CommandBus
{
    private array $calls = [];

    private function __construct()
    {}

    public static function create() : self
    {
        return new self();
    }

    public function send(object $command, array $metadata = [])
    {
        $this->calls[] = [$command, $metadata];
    }

    public function sendWithRouting(string $routingKey, $command, string $commandMediaType = MediaType::APPLICATION_X_PHP, array $metadata = [])
    {
        $this->calls[] = [$routingKey, $command, $commandMediaType, $metadata];
    }

    public function getCalls() : array
    {
        return $this->calls;
    }
}