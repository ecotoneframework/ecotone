<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Shopping;

/**
 * Class ToOrderRequestTransformer
 * @package Test\Ecotone\Messaging\Fixture\Behat\Shopping
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ToReservationRequestTransformer
{
    public function transform(string $bookName) : ReserveRequest
    {
        return new ReserveRequest($bookName);
    }
}