<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite\Fixtures\UnregisteredHandler\Command;

/**
 * licence Apache-2.0
 */
final class ReserveShippingSlot
{
    public function __construct(public string $orderId)
    {
    }
}
