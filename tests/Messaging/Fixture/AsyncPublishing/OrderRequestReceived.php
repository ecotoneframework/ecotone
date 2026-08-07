<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\AsyncPublishing;

/**
 * licence Apache-2.0
 */
final class OrderRequestReceived
{
    public function __construct(public string $order)
    {
    }
}
