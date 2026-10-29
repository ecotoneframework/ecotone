<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Booking;

use Ecotone\Messaging\Attribute\MessageGateway;

/**
 * licence Apache-2.0
 */
interface BookingService
{
    #[MessageGateway('bookingConfirmation')]
    public function checkIfIsBooked(string $flatNumber): bool;

    #[MessageGateway('bookingRequest')]
    public function bookFlat(string $flatNumber): void;
}
