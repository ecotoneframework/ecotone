<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Api\Gateway\DocumentStore;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Lite\Test\Configuration\InMemoryRepositoryBuilder;
use Ecotone\Messaging\Store\Document\InMemoryDocumentStore;
use Ecotone\Modelling\BaseEventSourcingConfiguration;
use Ecotone\Test\StubLogger;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\Ticket\AssignWorkerCommand;
use Test\Ecotone\Modelling\Fixture\Ticket\StartTicketCommand;
use Test\Ecotone\Modelling\Fixture\Ticket\Ticket;
use Test\Ecotone\Modelling\Fixture\Ticket\WorkerWasAssignedEvent;

/**
 * licence Apache-2.0
 * @internal
 */
final class AggregateSnapshotFoldShapeTest extends TestCase
{
    private const SNAPSHOTS = 'aggregate_snapshots_' . Ticket::class;
    private const FOLD_SHAPES = 'aggregate_snapshot_fold_shapes_' . Ticket::class;

    public function test_a_snapshot_written_before_ecotone_recorded_the_fold_shape_is_ignored(): void
    {
        $logger = StubLogger::create();
        $ecotone = $this->bootstrapWithSnapshots($logger);
        $ecotone->sendCommand(new StartTicketCommand('1'));
        $this->replaceSnapshotWithOneWrittenBeforeFoldShapesWereRecorded($ecotone, $this->ticketAssignedTo($ecotone, 'someone-who-never-was'));

        $ecotone->sendCommand(new AssignWorkerCommand('1', 'johny'));

        $this->assertSame('johny', $ecotone->getAggregate(Ticket::class, ['ticketId' => '1'])->getWorkerId());
        $this->assertCount(1, $logger->getError());
    }

    public function test_a_snapshot_without_a_fold_shape_is_replaced_by_the_next_write(): void
    {
        $logger = StubLogger::create();
        $ecotone = $this->bootstrapWithSnapshots($logger);
        $ecotone->sendCommand(new StartTicketCommand('1'));
        $this->replaceSnapshotWithOneWrittenBeforeFoldShapesWereRecorded($ecotone, $this->ticketAssignedTo($ecotone, 'someone-who-never-was'));

        $ecotone->sendCommand(new AssignWorkerCommand('1', 'johny'));
        $logger->clear();

        $this->assertSame('johny', $ecotone->getAggregate(Ticket::class, ['ticketId' => '1'])->getWorkerId());
        $this->assertCount(0, $logger->getError());
    }

    private function ticketAssignedTo(FlowTestSupport $ecotone, string $workerId): Ticket
    {
        $ticket = $ecotone->getAggregate(Ticket::class, ['ticketId' => '1']);
        $ticket->onWorkerWasAssigned(new WorkerWasAssignedEvent('1', $workerId));

        return $ticket;
    }

    private function replaceSnapshotWithOneWrittenBeforeFoldShapesWereRecorded(FlowTestSupport $ecotone, Ticket $ticket): void
    {
        $documentStore = $ecotone->getGateway(DocumentStore::class);
        $this->assertSame(1, $documentStore->countDocuments(self::SNAPSHOTS));
        $this->assertSame(1, $documentStore->countDocuments(self::FOLD_SHAPES));

        $documentStore->upsertDocument(self::SNAPSHOTS, '1', $ticket, DocumentStore::LAST_WRITE_WINS);
        $documentStore->dropCollection(self::FOLD_SHAPES);

        $this->assertSame(1, $documentStore->countDocuments(self::SNAPSHOTS));
    }

    private function bootstrapWithSnapshots(StubLogger $logger): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            [Ticket::class],
            [DocumentStore::class => InMemoryDocumentStore::createEmpty(), 'logger' => $logger],
            extensionObjects: [
                InMemoryRepositoryBuilder::createDefaultEventSourcedRepository(),
                BaseEventSourcingConfiguration::withDefaults()->withSnapshotsFor(Ticket::class, 1),
            ],
            enabledModules: [],
        );
    }
}
