<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit;

use Ecotone\Messaging\Precedence;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class PrecedenceTest extends TestCase
{
    public function test_async_publishing_await_runs_inside_transaction_and_outside_collector_release(): void
    {
        $this->assertGreaterThan(Precedence::DATABASE_TRANSACTION_PRECEDENCE, Precedence::DELIVERY_CONFIRMATION_PRECEDENCE);
        $this->assertGreaterThan(Precedence::DELIVERY_CONFIRMATION_PRECEDENCE, Precedence::COLLECTOR_SENDER_PRECEDENCE);
        $this->assertGreaterThan(Precedence::COLLECTOR_SENDER_PRECEDENCE, Precedence::DATABASE_OBJECT_MANAGER_PRECEDENCE);
        $this->assertGreaterThan(Precedence::DATABASE_OBJECT_MANAGER_PRECEDENCE, Precedence::LAZY_EVENT_PUBLICATION_PRECEDENCE);
    }
}
