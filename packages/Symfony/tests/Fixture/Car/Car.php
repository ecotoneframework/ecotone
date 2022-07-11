<?php

namespace Fixture\Car;

use Ecotone\Messaging\Attribute\ServiceActivator;

class Car
{
    /**
     * @var int
     */
    private $speed = 0;

    #[ServiceActivator(IncreaseSpeedGateway::CHANNEL_NAME)]
    public function increaseSpeed(int $amount): void
    {
        $this->speed += $amount;
    }

    #[ServiceActivator(StopGateway::CHANNEL_NAME)]
    public function stop(): void
    {
        $this->speed = 0;
    }

    #[ServiceActivator(GetSpeedGateway::CHANNEL_NAME)]
    public function getCurrentSpeed(): int
    {
        return $this->speed;
    }
}
