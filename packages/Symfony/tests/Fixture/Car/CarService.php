<?php

namespace Fixture\Car;

/**
 * Class CarService
 * @package Fixture\Car
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CarService
{
    /**
     * @var GetSpeedGateway
     */
    private $getSpeedGateway;
    /**
     * @var IncreaseSpeedGateway
     */
    private $increaseSpeedGateway;
    /**
     * @var StopGateway
     */
    private $stopGateway;

    /**
     * CarService constructor.
     * @param GetSpeedGateway $getSpeedGateway
     * @param IncreaseSpeedGateway $increaseSpeedGateway
     * @param StopGateway $stopGateway
     */
    public function __construct(GetSpeedGateway $getSpeedGateway, IncreaseSpeedGateway $increaseSpeedGateway, StopGateway $stopGateway)
    {
        $this->getSpeedGateway = $getSpeedGateway;
        $this->increaseSpeedGateway = $increaseSpeedGateway;
        $this->stopGateway = $stopGateway;
    }

    /**
     * @param int $amount
     */
    public function increaseSpeed(int $amount): void
    {
        $this->increaseSpeedGateway->increaseSpeed($amount);
    }

    /**
     * @return int
     */
    public function getSpeed(): int
    {
        return $this->getSpeedGateway->getSpeed();
    }

    public function stop(): void
    {
        $this->stopGateway->stop();
    }
}
