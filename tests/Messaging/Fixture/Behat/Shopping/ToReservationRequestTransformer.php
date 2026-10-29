<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Shopping;

use Ecotone\Messaging\Attribute\Transformer;

/**
 * licence Apache-2.0
 */
class ToReservationRequestTransformer
{
    #[Transformer('reserveRequest', outputChannelName: 'reserveRequestTransformer')]
    public function transform(string $bookName): ReserveRequest
    {
        return new ReserveRequest($bookName);
    }
}
