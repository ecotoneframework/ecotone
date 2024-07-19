<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Shopping;

/**
 * Class Bookshop
 * @package Test\Ecotone\Messaging\Fixture\Behat\Shopping
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class Bookshop
{
    /**
     * @var ReserveRequest[]|array
     */
    private $reservationRequests = [];

    /**
     * @param ReserveRequest $reservationRequest
     * @return BookWasReserved
     */
    public function reserve(ReserveRequest $reservationRequest): BookWasReserved
    {
        $this->reservationRequests[] = $reservationRequest;

        return new BookWasReserved($reservationRequest->name());
    }
}
