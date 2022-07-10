<?php


namespace Test\Ecotone\EventSourcing\Integration;


use Ecotone\EventSourcing\AggregateStreamMapping;
use Ecotone\EventSourcing\AggregateTypeMapping;
use Ecotone\EventSourcing\EventMapper;
use Ecotone\EventSourcing\EventSourcingConfiguration;
use Ecotone\EventSourcing\LazyProophEventStore;
use Ecotone\EventSourcing\EventSourcingRepositoryBuilder;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\InMemoryConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Store\Document\DocumentStore;
use Ecotone\Messaging\Store\Document\InMemoryDocumentStore;
use Ecotone\Modelling\Event;
use Ecotone\Modelling\EventStream;
use Ecotone\Modelling\SaveAggregateService;
use Ecotone\Modelling\SnapshotEvent;
use Ramsey\Uuid\Uuid;
use Test\Ecotone\EventSourcing\EventSourcingMessagingTest;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Event\TicketWasRegistered;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Ticket;
use Test\Ecotone\Modelling\Fixture\Ticket\WorkerWasAssignedEvent;

class EventSourcingRepositoryBuilderTest extends EventSourcingMessagingTest
{
    public function test_storing_and_retrieving()
    {
        $proophRepositoryBuilder = EventSourcingRepositoryBuilder::create(
            EventSourcingConfiguration::createWithDefaults()
                ->withSingleStreamPersistenceStrategy()
        );

        $ticketId = Uuid::uuid4()->toString();
        $ticketWasRegisteredEvent = new TicketWasRegistered($ticketId, "Johny", "standard");
        $ticketWasRegisteredEventAsArray = [
            "ticketId" => $ticketId,
            "assignedPerson" => "Johny",
            "ticketType" => "standard"
        ];

        $repository = $proophRepositoryBuilder->build(InMemoryChannelResolver::createEmpty(), $this->getReferenceSearchServiceWithConnection([
            EventMapper::class => EventMapper::createEmpty(),
            AggregateStreamMapping::class => AggregateStreamMapping::createEmpty(),
            AggregateTypeMapping::class => AggregateTypeMapping::createEmpty(),
            ConversionService::REFERENCE_NAME => InMemoryConversionService::createWithoutConversion()
                ->registerInPHPConversion($ticketWasRegisteredEvent, $ticketWasRegisteredEventAsArray)
                ->registerInPHPConversion($ticketWasRegisteredEventAsArray, $ticketWasRegisteredEvent)
        ]));

        $repository->save(["ticketId"=> $ticketId], Ticket::class, [$ticketWasRegisteredEvent], [
            MessageHeaders::MESSAGE_ID => Uuid::uuid4()->toString(),
            MessageHeaders::TIMESTAMP => 1610285647
        ], 0);

        $resultStream = $repository->findBy(Ticket::class, ["ticketId" => $ticketId]);
        $this->assertEquals(1, $resultStream->getAggregateVersion());
        $this->assertEquals($ticketWasRegisteredEvent, $resultStream->getEvents()[0]->getPayload());
    }

    public function test_retrieving_with_snaphots()
    {
        $proophRepositoryBuilder = EventSourcingRepositoryBuilder::create(
            EventSourcingConfiguration::createWithDefaults()
                ->withSnapshots([Ticket::class], 1)
        );

        $ticketId = Uuid::uuid4()->toString();
        $documentStore = InMemoryDocumentStore::createEmpty();
        $ticket = new Ticket();
        $ticketWasRegistered = new TicketWasRegistered($ticketId, "Johny", "standard");
        $ticket->setVersion(1);
        $ticketWasRegisteredEventAsArray = [
            "ticketId" => $ticketId,
            "ticketType" => "standard"
        ];
        $workerWasAssigned = new WorkerWasAssignedEvent($ticketId, 100);
        $workerWasAssignedAsArray = [
            "ticketId" => $ticketId,
            "assignedWorkerId" => 100
        ];

        $ticket->applyTicketWasRegistered($ticketWasRegistered);
        $documentStore->addDocument(SaveAggregateService::getSnapshotCollectionName(Ticket::class), $ticketId, $ticket);

        $repository = $proophRepositoryBuilder->build(InMemoryChannelResolver::createEmpty(), $this->getReferenceSearchServiceWithConnection([
            EventMapper::class => EventMapper::createEmpty(),
            AggregateStreamMapping::class => AggregateStreamMapping::createEmpty(),
            AggregateTypeMapping::class => AggregateTypeMapping::createEmpty(),
            ConversionService::REFERENCE_NAME => InMemoryConversionService::createWithoutConversion()
                ->registerInPHPConversion($ticketWasRegistered, $ticketWasRegisteredEventAsArray)
                ->registerInPHPConversion($ticketWasRegisteredEventAsArray, $ticketWasRegistered)
                ->registerInPHPConversion($workerWasAssigned, $workerWasAssignedAsArray)
                ->registerInPHPConversion($workerWasAssignedAsArray, $workerWasAssigned),
            DocumentStore::class => $documentStore
        ]));

        $repository->save(["ticketId"=> $ticketId], Ticket::class, [$ticketWasRegistered, $workerWasAssigned], [
            MessageHeaders::TIMESTAMP => 1610285647
        ], 0);

        $resultStream = $repository->findBy(Ticket::class, ["ticketId" => $ticketId]);
        $this->assertEquals(2, $resultStream->getAggregateVersion());
        $this->assertEquals(new SnapshotEvent($ticket), $resultStream->getEvents()[0]);
        $this->assertEquals($workerWasAssigned, $resultStream->getEvents()[1]->getPayload());
    }

    public function test_retrieving_with_snaphots_not_extist_in_documentstore()
    {
        $proophRepositoryBuilder = EventSourcingRepositoryBuilder::create(
            EventSourcingConfiguration::createWithDefaults()
                ->withSnapshots([Ticket::class], 1)
        );

        $ticketId = Uuid::uuid4()->toString();
        $documentStore = InMemoryDocumentStore::createEmpty();
        $ticket = new Ticket();
        $ticketWasRegistered = new TicketWasRegistered($ticketId, "Johny", "standard");
        $ticket->setVersion(1);
        $ticketWasRegisteredEventAsArray = [
            "ticketId" => $ticketId,
            "ticketType" => "standard"
        ];
        $workerWasAssigned = new WorkerWasAssignedEvent($ticketId, 100);
        $workerWasAssignedAsArray = [
            "ticketId" => $ticketId,
            "assignedWorkerId" => 100
        ];

        $ticket->applyTicketWasRegistered($ticketWasRegistered);

        $repository = $proophRepositoryBuilder->build(InMemoryChannelResolver::createEmpty(), $this->getReferenceSearchServiceWithConnection([
            EventMapper::class => EventMapper::createEmpty(),
            AggregateStreamMapping::class => AggregateStreamMapping::createEmpty(),
            AggregateTypeMapping::class => AggregateTypeMapping::createEmpty(),
            ConversionService::REFERENCE_NAME => InMemoryConversionService::createWithoutConversion()
                ->registerInPHPConversion($ticketWasRegistered, $ticketWasRegisteredEventAsArray)
                ->registerInPHPConversion($ticketWasRegisteredEventAsArray, $ticketWasRegistered)
                ->registerInPHPConversion($workerWasAssigned, $workerWasAssignedAsArray)
                ->registerInPHPConversion($workerWasAssignedAsArray, $workerWasAssigned),
            DocumentStore::class => $documentStore
        ]));

        $repository->save(["ticketId"=> $ticketId], Ticket::class, [$ticketWasRegistered, $workerWasAssigned], [
            MessageHeaders::TIMESTAMP => 1610285647
        ], 0);

        $resultStream = $repository->findBy(Ticket::class, ["ticketId" => $ticketId]);
        $this->assertEquals(2, $resultStream->getAggregateVersion());
        $this->assertEquals($workerWasAssigned, $resultStream->getEvents()[1]->getPayload());
    }

    public function test_having_two_streams_for_difference_instances_of_same_aggregate_using_aggregate_stream_strategy()
    {
        $proophRepositoryBuilder = EventSourcingRepositoryBuilder::create(
            EventSourcingConfiguration::createWithDefaults()
                ->withStreamPerAggregatePersistenceStrategy()
        );

        $firstTicketAggregate = Uuid::uuid4()->toString();
        $secondTicketAggregate = Uuid::uuid4()->toString();
        $firstTicketWasRegisteredEvent = new TicketWasRegistered($firstTicketAggregate, "Johny", "standard");
        $firstTicketWasRegisteredEventAsArray = [
            "ticketId" => $firstTicketAggregate,
            "assignedPerson" => "Johny",
            "ticketType" => "standard"
        ];
        $secondTicketWasRegisteredEvent = new TicketWasRegistered($secondTicketAggregate, "Johny", "standard");
        $secondTicketWasRegisteredEventAsArray = [
            "ticketId" => $secondTicketAggregate,
            "assignedPerson" => "Johny",
            "ticketType" => "standard"
        ];

        $repository = $proophRepositoryBuilder->build(InMemoryChannelResolver::createEmpty(), $this->getReferenceSearchServiceWithConnection([
            EventMapper::class => EventMapper::createEmpty(),
            AggregateStreamMapping::class => AggregateStreamMapping::createEmpty(),
            AggregateTypeMapping::class => AggregateTypeMapping::createEmpty(),
            ConversionService::REFERENCE_NAME => InMemoryConversionService::createWithoutConversion()
                ->registerInPHPConversion($firstTicketWasRegisteredEvent, $firstTicketWasRegisteredEventAsArray)
                ->registerInPHPConversion($firstTicketWasRegisteredEventAsArray, $firstTicketWasRegisteredEvent)
                ->registerInPHPConversion($secondTicketWasRegisteredEvent, $secondTicketWasRegisteredEventAsArray)
                ->registerInPHPConversion($secondTicketWasRegisteredEventAsArray, $secondTicketWasRegisteredEvent)
        ]));

        $repository->save(["ticketId"=> $firstTicketAggregate], Ticket::class, [$firstTicketWasRegisteredEvent], [
            MessageHeaders::MESSAGE_ID => Uuid::uuid4()->toString(),
            MessageHeaders::TIMESTAMP => 1610285647
        ], 0);

        $repository->save(["ticketId"=> $secondTicketAggregate], Ticket::class, [$secondTicketWasRegisteredEvent], [
            MessageHeaders::MESSAGE_ID => Uuid::uuid4()->toString(),
            MessageHeaders::TIMESTAMP => 1610285647
        ], 0);

        $resultStream = $repository->findBy(Ticket::class, ["ticketId"=> $firstTicketAggregate]);
        $this->assertEquals(1, $resultStream->getAggregateVersion());
        $this->assertEquals($firstTicketWasRegisteredEvent, $resultStream->getEvents()[0]->getPayload());

        $resultStream = $repository->findBy(Ticket::class, ["ticketId"=> $secondTicketAggregate]);
        $this->assertEquals(1, $resultStream->getAggregateVersion());
        $this->assertEquals($secondTicketWasRegisteredEvent, $resultStream->getEvents()[0]->getPayload());
    }

    public function test_having_two_streams_for_difference_instances_of_same_aggregate_using_single_stream_strategy()
    {
        $proophRepositoryBuilder = EventSourcingRepositoryBuilder::create(
            EventSourcingConfiguration::createWithDefaults()
                ->withSingleStreamPersistenceStrategy()
        );

        $firstTicketAggregate = Uuid::uuid4()->toString();
        $secondTicketAggregate = Uuid::uuid4()->toString();
        $firstTicketWasRegisteredEvent = new TicketWasRegistered($firstTicketAggregate, "Johny", "standard");
        $firstTicketWasRegisteredEventAsArray = [
            "ticketId" => $firstTicketAggregate,
            "assignedPerson" => "Johny",
            "ticketType" => "standard"
        ];
        $secondTicketWasRegisteredEvent = new TicketWasRegistered($secondTicketAggregate, "Johny", "standard");
        $secondTicketWasRegisteredEventAsArray = [
            "ticketId" => $secondTicketAggregate,
            "assignedPerson" => "Johny",
            "ticketType" => "standard"
        ];

        $repository = $proophRepositoryBuilder->build(InMemoryChannelResolver::createEmpty(), $this->getReferenceSearchServiceWithConnection([
            EventMapper::class => EventMapper::createEmpty(),
            AggregateStreamMapping::class => AggregateStreamMapping::createEmpty(),
            AggregateTypeMapping::class => AggregateTypeMapping::createEmpty(),
            ConversionService::REFERENCE_NAME => InMemoryConversionService::createWithoutConversion()
                ->registerInPHPConversion($firstTicketWasRegisteredEvent, $firstTicketWasRegisteredEventAsArray)
                ->registerInPHPConversion($firstTicketWasRegisteredEventAsArray, $firstTicketWasRegisteredEvent)
                ->registerInPHPConversion($secondTicketWasRegisteredEvent, $secondTicketWasRegisteredEventAsArray)
                ->registerInPHPConversion($secondTicketWasRegisteredEventAsArray, $secondTicketWasRegisteredEvent)
        ]));

        $repository->save(["ticketId"=> $firstTicketAggregate], Ticket::class, [$firstTicketWasRegisteredEvent], [
            MessageHeaders::MESSAGE_ID => Uuid::uuid4()->toString(),
            MessageHeaders::TIMESTAMP => 1610285647
        ], 0);

        $repository->save(["ticketId"=> $secondTicketAggregate], Ticket::class, [$secondTicketWasRegisteredEvent], [
            MessageHeaders::MESSAGE_ID => Uuid::uuid4()->toString(),
            MessageHeaders::TIMESTAMP => 1610285647
        ], 0);

        $resultStream = $repository->findBy(Ticket::class, ["ticketId"=> $firstTicketAggregate]);
        $this->assertEquals(1, $resultStream->getAggregateVersion());
        $this->assertEquals($firstTicketWasRegisteredEvent, $resultStream->getEvents()[0]->getPayload());

        $resultStream = $repository->findBy(Ticket::class, ["ticketId"=> $secondTicketAggregate]);
        $this->assertEquals(1, $resultStream->getAggregateVersion());
        $this->assertEquals($secondTicketWasRegisteredEvent, $resultStream->getEvents()[0]->getPayload());
    }

    public function test_handling_connection_as_registry()
    {
        $proophRepositoryBuilder = EventSourcingRepositoryBuilder::create(
            EventSourcingConfiguration::createWithDefaults()
                ->withSingleStreamPersistenceStrategy()
        );

        $ticketId = Uuid::uuid4()->toString();
        $ticketWasRegisteredEvent = new TicketWasRegistered($ticketId, "Johny", "standard");
        $ticketWasRegisteredEventAsArray = [
            "ticketId" => $ticketId,
            "assignedPerson" => "Johny",
            "ticketType" => "standard"
        ];

        $repository = $proophRepositoryBuilder->build(InMemoryChannelResolver::createEmpty(), $this->getReferenceSearchServiceWithConnection([
            EventMapper::class => EventMapper::createEmpty(),
            AggregateStreamMapping::class => AggregateStreamMapping::createEmpty(),
            AggregateTypeMapping::class => AggregateTypeMapping::createEmpty(),
            ConversionService::REFERENCE_NAME => InMemoryConversionService::createWithoutConversion()
                ->registerInPHPConversion($ticketWasRegisteredEvent, $ticketWasRegisteredEventAsArray)
                ->registerInPHPConversion($ticketWasRegisteredEventAsArray, $ticketWasRegisteredEvent)
        ], true));

        $repository->save(["ticketId"=> $ticketId], Ticket::class, [$ticketWasRegisteredEvent], [
            MessageHeaders::MESSAGE_ID => Uuid::uuid4()->toString(),
            MessageHeaders::TIMESTAMP => 1610285647
        ], 0);

        $resultStream = $repository->findBy(Ticket::class, ["ticketId" => $ticketId]);
        $this->assertEquals(1, $resultStream->getAggregateVersion());
        $this->assertEquals($ticketWasRegisteredEvent, $resultStream->getEvents()[0]->getPayload());
    }
}
