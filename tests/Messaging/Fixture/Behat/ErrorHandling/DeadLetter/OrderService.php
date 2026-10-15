<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling\DeadLetter;

use Ecotone\Api\Attribute\InternalHandler;
use InvalidArgumentException;

/**
 * licence Apache-2.0
 */
class OrderService
{
    #[InternalHandler(ErrorConfigurationContext::INPUT_CHANNEL, endpointId: 'orderService')]
    public function order(string $orderName): void
    {
        throw new InvalidArgumentException('exception');
    }
}
