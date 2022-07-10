<?php


namespace Test\Ecotone\Messaging\Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterfaceCalculatingService
 * @package Fixture\Service\ServiceInterface
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceInterfaceCalculatingService
{
    /**
     * @param int $startingAmount
     * @return int
     */
    public function calculate(int $startingAmount) : int;
}