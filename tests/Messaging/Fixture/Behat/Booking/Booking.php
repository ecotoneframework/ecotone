<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Booking;

use Ecotone\Messaging\Attribute\ServiceActivator;

/**
 * licence Apache-2.0
 */
class Booking
{
    private array $bookedFlats = [];

    #[ServiceActivator('bookingRequest')]
    public function book(string $flatNumber): void
    {
        $this->bookedFlats[] = $flatNumber;
    }

    #[ServiceActivator('bookingConfirmation')]
    public function isBooked(string $flatNumber): bool
    {
        foreach ($this->bookedFlats as $bookedFlat) {
            if ($bookedFlat == $flatNumber) {
                return true;
            }
        }

        return false;
    }
}
