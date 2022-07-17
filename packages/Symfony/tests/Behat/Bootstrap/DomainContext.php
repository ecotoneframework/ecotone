<?php

namespace Test\Ecotone\Symfony\Behat\Bootstrap;

use Behat\Behat\Context\Context;
use Fixture\Car\CarService;
use PHPUnit\Framework\TestCase;

/**
 * Defines application features from the specific context.
 */
class DomainContext implements Context
{
    /**
     * @var CarService
     */
    private $carService;

    public function __construct(CarService $carService)
    {
        $this->carService = $carService;
    }

    /**
     * @Given there is car
     */
    public function thereIsCar()
    {
    }

    /**
     * @When I speed up to :speedAmount
     * @param int $speedAmount
     */
    public function iSpeedUpTo(int $speedAmount)
    {
        $this->carService->increaseSpeed($speedAmount);
    }

    /**
     * @Then there speed should be :speedAmount
     * @param int $speedAmount
     */
    public function thereSpeedShouldBe(int $speedAmount)
    {
        TestCase::assertEquals(
            $speedAmount,
            $this->carService->getSpeed()
        );
    }
}
