<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Shopping;

use Ecotone\Api\Attribute\InternalHandler;

/**
 * licence Apache-2.0
 */
class Bookshop
{
    private array $reservationRequests = [];

    #[InternalHandler('reserveRequestTransformer')]
    public function reserve(ReserveRequest $reservationRequest): BookWasReserved
    {
        $this->reservationRequests[] = $reservationRequest;

        return new BookWasReserved($reservationRequest->name());
    }
}
