<?php


namespace Test\Ecotone\EventSourcing\Fixture\TicketEmittingProjection;

use Doctrine\DBAL\Connection;
use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\EventSourcing\Attribute\ProjectionDelete;
use Ecotone\EventSourcing\Attribute\ProjectionInitialization;
use Ecotone\EventSourcing\Attribute\ProjectionReset;
use Ecotone\EventSourcing\EventStore;
use Ecotone\EventSourcing\EventStreamEmitter;
use Ecotone\EventSourcing\LazyProophProjectionManager;
use Ecotone\EventSourcing\ProjectionManager;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Event\TicketWasClosed;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Event\TicketWasRegistered;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Ticket;

#[Projection(self::NAME, Ticket::class)]
class InProgressTicketList
{
    const NAME = "inProgressTicketList";
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[QueryHandler("getInProgressTickets")]
    public function getTickets() : array
    {
        return $this->connection->executeQuery(<<<SQL
    SELECT * FROM in_progress_tickets
    ORDER BY ticket_id ASC
SQL)->fetchAllAssociative();
    }

    #[EventHandler(endpointId: "inProgressTicketList.addTicket")]
    public function addTicket(TicketWasRegistered $event, EventStreamEmitter $eventStreamEmitter) : void
    {
        $eventStreamEmitter->linkTo(LazyProophProjectionManager::getProjectionStreamName(self::NAME), [new TicketListUpdated($event->getTicketId())]);

        $this->connection->executeStatement(<<<SQL
    INSERT INTO in_progress_tickets VALUES (?,?)
SQL, [$event->getTicketId(), $event->getTicketType()]);
    }

    #[EventHandler(endpointId: "inProgressTicketList.closeTicket")]
    public function closeTicket(TicketWasClosed $event, EventStreamEmitter $eventStreamEmitter) : void
    {
        $eventStreamEmitter->emit([new TicketListUpdated($event->getTicketId())]);

        $this->connection->executeStatement(<<<SQL
    DELETE FROM in_progress_tickets WHERE ticket_id = ?
SQL, [$event->getTicketId()]);
    }

    #[ProjectionInitialization]
    public function initialization() : void
    {
        $this->connection->executeStatement(<<<SQL
    CREATE TABLE IF NOT EXISTS in_progress_tickets (
        ticket_id VARCHAR(36) PRIMARY KEY,
        ticket_type VARCHAR(25)
    )
SQL);
    }

    #[ProjectionDelete]
    public function delete() : void
    {
        $this->connection->executeStatement(<<<SQL
    DROP TABLE in_progress_tickets
SQL);
    }

    #[ProjectionReset]
    public function reset() : void
    {
        $this->connection->executeStatement(<<<SQL
    DELETE FROM in_progress_tickets
SQL);
    }
}