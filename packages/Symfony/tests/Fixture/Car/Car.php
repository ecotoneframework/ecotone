<?php

namespace Fixture\Car;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Fixture\Car\IncreaseSpeedGateway;
use Fixture\Car\GetSpeedGateway;
use Fixture\Car\StopGateway;

class Car
{
    /**
     * @var int
     */
    private $speed = 0;

    #[ServiceActivator(IncreaseSpeedGateway::CHANNEL_NAME)]
    public function increaseSpeed(int $amount) : void
    {
        $this->speed += $amount;
    }

    #[ServiceActivator(StopGateway::CHANNEL_NAME)]
    public function stop() : void
    {
        $this->speed = 0;
    }

    #[ServiceActivator(GetSpeedGateway::CHANNEL_NAME)]
    public function getCurrentSpeed() : int
    {
        return $this->speed;
    }
}