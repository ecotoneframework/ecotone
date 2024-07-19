<?php

namespace Test\Ecotone\Messaging\Fixture\Service\ServiceInterface;

/**
 * Interface ServiceInterfaceCalculatingService
 * @package Fixture\Service\ServiceInterface
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface ServiceInterfaceCalculatingService
{
    /**
     * @param int $startingAmount
     * @return int
     */
    public function calculate(int $startingAmount): int;
}
