<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Booking;

/**
 * Class BookingService
 * @package Test\Ecotone\Messaging\Fixture\Behat\Booking
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class Booking
{
    /**
     * @var array|string[]
     */
    private $bookedFlats = [];

    /**
     * @param string $flatNumber
     */
    public function book(string $flatNumber) : void
    {
        $this->bookedFlats[] = $flatNumber;
    }

    /**
     * @param string $flatNumber
     * @return bool
     */
    public function isBooked(string $flatNumber) : bool
    {
        foreach ($this->bookedFlats as $bookedFlat) {
            if ($bookedFlat == $flatNumber) {
                return true;
            }
        }

        return false;
    }
}