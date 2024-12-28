<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Handler\UnionTypeDescriptor;
use Ecotone\Modelling\CommandBus;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder\CreateAggregate;
use Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder\DoSomething;
use Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder\EventSourcingAggregateWithInternalRecorder;
use Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder\StateBasedAggregateWithInternalRecorder;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateWithoutMessageClassesExample;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\GetOrderAmountQuery;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\Order;
use Test\Ecotone\Modelling\Fixture\QueryHandlerAggregate\BigBox;
use Test\Ecotone\Modelling\Fixture\QueryHandlerAggregate\Box;
use Test\Ecotone\Modelling\Fixture\QueryHandlerAggregate\CreateStorage;
use Test\Ecotone\Modelling\Fixture\QueryHandlerAggregate\SmallBox;
use Test\Ecotone\Modelling\Fixture\QueryHandlerAggregate\Storage;
use Test\Ecotone\Modelling\Fixture\QueryHandlerAggregate\StorageService;
use Test\Ecotone\Modelling\Fixture\Ticket\AssignWorkerCommand;
use Test\Ecotone\Modelling\Fixture\Ticket\StartTicketCommand;
use Test\Ecotone\Modelling\Fixture\Ticket\Ticket;
use Test\Ecotone\Modelling\Fixture\Ticket\WorkerWasAssignedEvent;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class CallAggregateServiceBuilderTest extends TestCase
{
    public function test_calling_existing_aggregate_method_with_command_class(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [AggregateWithoutMessageClassesExample::class, InMemoryStandardRepository::class],
            [InMemoryStandardRepository::createWith([AggregateWithoutMessageClassesExample::create($id = 1)])],
            addInMemoryStateStoredRepository: false,
            addInMemoryEventSourcedRepository: false,
        )
            ->sendCommandWithRoutingKey('doSomething', metadata: ['aggregate.id' => $id]);

        $this->assertTrue(
            $ecotoneLite
                ->getAggregate(AggregateWithoutMessageClassesExample::class, $id)
                ->getChangedState()
        );
    }

    public function test_calling_aggregate_for_query_handler_with_return_value(): void
    {
        $orderAmount = 5;

        $this->assertEquals(
            $orderAmount,
            EcotoneLite::bootstrapFlowTesting(
                [Order::class]
            )
                ->sendCommand(CreateOrderCommand::createWith(1, $orderAmount, 'Poland'))
                ->sendQueryWithRouting('get_order_amount_channel', GetOrderAmountQuery::createWith(1))
        );
    }

    public function test_providing_correct_result_type_for_reply_message_when_result_is_collection(): void
    {
        $storageId = Uuid::uuid4()->toString();

        $this->assertEquals(
            TypeDescriptor::createCollection(SmallBox::class),
            EcotoneLite::bootstrapFlowTesting(
                [Storage::class, StorageService::class],
            )
                ->sendCommand(new CreateStorage($storageId, [SmallBox::create(1)], []))
                ->getGateway(StorageService::class)
                ->getSmallBoxes($storageId)
                ->getHeaders()->getContentType()->getTypeParameter()
        );
    }

    public function test_providing_correct_result_type_for_reply_message_when_result_is_collection_of_unions(): void
    {
        $storageId = Uuid::uuid4()->toString();

        $this->assertEquals(
            new UnionTypeDescriptor([
                TypeDescriptor::createCollection(Box::class),
                TypeDescriptor::createCollection(BigBox::class),
            ]),
            EcotoneLite::bootstrapFlowTesting(
                [Storage::class, StorageService::class],
            )
                ->sendCommand(new CreateStorage($storageId, [SmallBox::create(1)], []))
                ->getGateway(StorageService::class)
                ->getBoxes($storageId)
                ->getHeaders()->getContentType()->getTypeParameter()
        );
    }

    public function test_calling_aggregate_query_handler_returning_null_value(): void
    {
        $this->assertNull(
            EcotoneLite::bootstrapFlowTesting(
                [Order::class]
            )
                ->sendCommand(CreateOrderCommand::createWith(1, 5, 'Poland'))
                ->sendQueryWithRouting('get_customer_id', metadata: ['aggregate.id' => 1])
        );
    }

    public function test_calling_aggregate_for_query_handler_with_no_query(): void
    {
        $this->assertEquals(
            true,
            EcotoneLite::bootstrapFlowTesting(
                [AggregateWithoutMessageClassesExample::class],
            )
                ->sendCommandWithRoutingKey('createAggregateNoParams', $id = 1)
                ->sendQueryWithRouting('querySomething', metadata: ['aggregate.id' => $id])
        );
    }

    public function test_calling_factory_method_for_event_sourced_aggregate(): void
    {
        $commandToRun = new StartTicketCommand(1);

        $reply = EcotoneLite::bootstrapFlowTesting(
            [Ticket::class],
        )
            ->getGateway(CommandBus::class)
            ->send($commandToRun);

        $this->assertEquals(1, $reply);
    }

    public function test_calling_factory_method_for_event_sourced_aggregate_with_internal_recorder(): void
    {
        $commandToRun = new CreateAggregate(1);

        $reply = EcotoneLite::bootstrapFlowTesting(
            [EventSourcingAggregateWithInternalRecorder::class],
        )
            ->getGateway(CommandBus::class)
            ->send($commandToRun);

        $this->assertEquals(1, $reply);
    }

    public function test_calling_action_method_for_existing_event_sourced_aggregate(): void
    {
        $ticketId = 1;
        $workerId = 100;

        $reply = EcotoneLite::bootstrapFlowTesting(
            [Ticket::class],
        )
            ->sendCommand(new StartTicketCommand($ticketId))
            ->getGateway(CommandBus::class)
            ->send(new AssignWorkerCommand($ticketId, $workerId));

        $this->assertEquals([new WorkerWasAssignedEvent($ticketId, $workerId)], $reply);
    }

    public function test_calling_action_method_of_existing_event_sourcing_aggregate_with_internal_recorder(): void
    {
        $commandToRun = new DoSomething(1);

        $reply = EcotoneLite::bootstrapFlowTesting(
            [EventSourcingAggregateWithInternalRecorder::class],
        )
            ->sendCommand(new CreateAggregate(1))
            ->getGateway(CommandBus::class)
            ->send($commandToRun);


        $this->assertNull($reply);
    }

    public function test_calling_action_method_of_existing_state_based_aggregate_with_internal_recorder(): void
    {
        $commandToRun = new DoSomething(1);

        $reply = EcotoneLite::bootstrapFlowTesting(
            [StateBasedAggregateWithInternalRecorder::class],
        )
            ->sendCommand(new CreateAggregate(1))
            ->getGateway(CommandBus::class)
            ->send($commandToRun);


        $this->assertNull($reply);
    }
}
