<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow\Fixture;

/**
 * licence Apache-2.0
 */
final class FlowOrderPlaced
{
    public function __construct(public readonly string $orderId)
    {
    }
}
