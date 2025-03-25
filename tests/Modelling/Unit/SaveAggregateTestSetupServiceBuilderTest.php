<?php

declare(strict_types=1);

namespace Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Test\Ecotone\Modelling\Fixture\SimplifiedAggregate\SimplifiedAggregate;
use Test\Ecotone\Modelling\Fixture\Ticket\Ticket;
use Test\Ecotone\Modelling\Fixture\Ticket\TicketWasStartedEvent;
use Test\Ecotone\Modelling\Fixture\Ticket\WorkerWasAssignedEvent;

/**
 * licence Apache-2.0
 * @internal
 */
final class SaveAggregateTestSetupServiceBuilderTest extends TestCase
{
    public function test_using_initial_state_for_event_sourced_aggregates(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting([Ticket::class]);

        $ticketId = Uuid::uuid4()->toString();

        $this->assertEquals(
            'Elvis',
            $ecotoneTestSupport
                ->withEventsFor($ticketId, Ticket::class, [
                    new TicketWasStartedEvent($ticketId),
                ])
                ->withEventsFor($ticketId, Ticket::class, [
                    new WorkerWasAssignedEvent($ticketId, 'Elvis'),
                ], 1)
                ->getAggregate(Ticket::class, ['ticketId' => $ticketId])
                ->getWorkerId()
        );
    }

    public function test_using_initial_state_for_state_aggregates(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting([SimplifiedAggregate::class]);

        $this->assertTrue(
            $ecotoneTestSupport
                ->withStateFor(new SimplifiedAggregate(id: $id = '123', isEnabled: true))
                ->getAggregate(SimplifiedAggregate::class, $id)
                ->isEnabled()
        );
    }
}
