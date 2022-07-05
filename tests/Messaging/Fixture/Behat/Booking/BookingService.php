<?php

namespace Ecotone\Tests\Messaging\Fixture\Behat\Booking;

/**
 * Interface BookingService
 * @package Ecotone\Tests\Messaging\Fixture\Behat\Booking
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface BookingService
{
    /**
     * @param string $flatNumber
     * @return bool
     */
    public function checkIfIsBooked(string $flatNumber) : bool;

    /**
     * @param string $flatNumber
     * @return void
     */
    public function bookFlat(string $flatNumber) : void;
}