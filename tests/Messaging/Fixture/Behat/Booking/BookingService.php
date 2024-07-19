<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Booking;

/**
 * Interface BookingService
 * @package Test\Ecotone\Messaging\Fixture\Behat\Booking
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface BookingService
{
    /**
     * @param string $flatNumber
     * @return bool
     */
    public function checkIfIsBooked(string $flatNumber): bool;

    /**
     * @param string $flatNumber
     * @return void
     */
    public function bookFlat(string $flatNumber): void;
}
