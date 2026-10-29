<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace EventSourcing\Fixture\Projecting;

class OrderCanceled
{
    public function __construct(
        public readonly string $orderId,
    ) {
    }
}
