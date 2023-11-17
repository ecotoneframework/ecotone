<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Handler\UnionTypeDescriptor;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\CallAggregate\CallAggregateServiceBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\InMemoryEventSourcedRepository;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder\AggregateCreated;
use Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder\CreateAggregate;
use Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder\CreateSomething;
use Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder\DoSomething;
use Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder\EventSourcingAggregateWithInternalRecorder;
use Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder\Something;
use Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder\SomethingWasCreated;
use Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder\SomethingWasDone;
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
use Test\Ecotone\Modelling\Fixture\Ticket\AssignWorkerCommand;
use Test\Ecotone\Modelling\Fixture\Ticket\StartTicketCommand;
use Test\Ecotone\Modelling\Fixture\Ticket\Ticket;
use Test\Ecotone\Modelling\Fixture\Ticket\TicketWasStartedEvent;
use Test\Ecotone\Modelling\Fixture\Ticket\WorkerWasAssignedEvent;

/**
 * @internal
 */
class CallAggregateServiceBuilderTest extends TestCase
{
    public function test_calling_existing_aggregate_method_with_command_class(): void
    {
        $aggregate                      = AggregateWithoutMessageClassesExample::create();
        $aggregateCallingCommandHandler = CallAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(AggregateWithoutMessageClassesExample::class)),
            'doSomething',
            true,
            InterfaceToCallRegistry::createEmpty()
        );

        $aggregateCommandHandler = ComponentTestBuilder::create()
            ->withReference('repository', InMemoryStandardRepository::createEmpty())
            ->build($aggregateCallingCommandHandler);

        $this->assertNull($aggregate->getChangedState());

        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload(['id' => 1])
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $aggregate)
                ->build()
        );

        $this->assertTrue($aggregate->getChangedState());
    }

    public function test_calling_aggregate_for_query_handler_with_return_value(): void
    {
        $orderAmount = 5;
        $order = Order::createWith(CreateOrderCommand::createWith(1, $orderAmount, 'Poland'));
        $order->increaseAggregateVersion();

        $aggregateCallingCommandHandler = CallAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Order::class)),
            'getAmountWithQuery',
            true,
            InterfaceToCallRegistry::createEmpty()
        );

        $aggregateQueryHandler = ComponentTestBuilder::create()
            ->withReference('orderRepository', InMemoryStandardRepository::createEmpty())
            ->build($aggregateCallingCommandHandler);

        $replyChannel = QueueChannel::create();
        $aggregateQueryHandler->handle(
            MessageBuilder::withPayload(GetOrderAmountQuery::createWith(1))
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $order)
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(
            $orderAmount,
            $replyChannel->receive()->getPayload()
        );
    }

    public function test_providing_correct_result_type_for_reply_message_when_result_is_collection(): void
    {
        $aggregateId = 1;
        $aggregate = Storage::create(new CreateStorage($aggregateId, [SmallBox::create(1)], []));

        $aggregateCallingCommandHandler = CallAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Storage::class)),
            'getSmallBoxes',
            false,
            InterfaceToCallRegistry::createEmpty()
        );

        $aggregateQueryHandler = ComponentTestBuilder::create()
            ->withReference(InMemoryStandardRepository::class, InMemoryStandardRepository::createEmpty())
            ->build($aggregateCallingCommandHandler);

        $replyChannel = QueueChannel::create();
        $aggregateQueryHandler->handle(
            MessageBuilder::withPayload(['storageId' => $aggregateId])
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $aggregate)
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(
            TypeDescriptor::createCollection(SmallBox::class),
            $replyChannel->receive()->getHeaders()->getContentType()->getTypeParameter()
        );
    }

    public function test_providing_correct_result_type_for_reply_message_when_result_is_collection_of_unions(): void
    {
        $aggregateId = 1;
        $aggregate = Storage::create(new CreateStorage($aggregateId, [SmallBox::create(1)], [BigBox::create(2)]));

        $aggregateCallingCommandHandler = CallAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Storage::class)),
            'getBigBoxes',
            false,
            InterfaceToCallRegistry::createEmpty()
        );

        $aggregateQueryHandler = ComponentTestBuilder::create()
            ->withReference(InMemoryStandardRepository::class, InMemoryStandardRepository::createEmpty())
            ->build($aggregateCallingCommandHandler);

        $replyChannel = QueueChannel::create();
        $aggregateQueryHandler->handle(
            MessageBuilder::withPayload(['storageId' => $aggregateId])
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $aggregate)
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(
            new UnionTypeDescriptor([
                TypeDescriptor::createCollection(Box::class),
                TypeDescriptor::createCollection(BigBox::class),
            ]),
            $replyChannel->receive()->getHeaders()->getContentType()->getTypeParameter()
        );
    }

    public function test_calling_aggregate_query_handler_returning_null_value(): void
    {
        $orderAmount = 5;
        $order = Order::createWith(CreateOrderCommand::createWith(1, $orderAmount, 'Poland'));
        $order->increaseAggregateVersion();

        $aggregateCallingCommandHandler = CallAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Order::class)),
            'getCustomerId',
            false,
            InterfaceToCallRegistry::createEmpty()
        );

        $aggregateQueryHandler = ComponentTestBuilder::create()
            ->withReference('orderRepository', InMemoryStandardRepository::createEmpty())
            ->build($aggregateCallingCommandHandler);

        $replyChannel = QueueChannel::create();
        $aggregateQueryHandler->handle(
            MessageBuilder::withPayload(['orderId' => 1])
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $order)
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertNull($replyChannel->receive());
    }

    public function test_calling_aggregate_for_query_handler_with_no_query(): void
    {
        $aggregate = AggregateWithoutMessageClassesExample::create(['id' => 1]);
        $aggregateCallingCommandHandler = CallAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(AggregateWithoutMessageClassesExample::class)),
            'querySomething',
            false,
            InterfaceToCallRegistry::createEmpty()
        );

        $aggregateQueryHandler = ComponentTestBuilder::create()
            ->withReference('repository', InMemoryStandardRepository::createWith([$aggregate]))
            ->build($aggregateCallingCommandHandler);

        $replyChannel = QueueChannel::create();
        $aggregateQueryHandler->handle(
            MessageBuilder::withPayload(['id' => 1])
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $aggregate)
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(
            true,
            $replyChannel->receive()->getPayload()
        );
    }

    public function test_calling_factory_method_for_event_sourced_aggregate(): void
    {
        $commandToRun = new StartTicketCommand(1);

        $aggregateCallingCommandHandler = CallAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Ticket::class)),
            'start',
            true,
            InterfaceToCallRegistry::createEmpty()
        )
            ->withInputChannelName('inputChannel');

        $replyChannel = QueueChannel::create();
        $inMemoryEventSourcedRepository = InMemoryEventSourcedRepository::createEmpty();

        $aggregateCommandHandler = ComponentTestBuilder::create()
            ->withReference('repository', $inMemoryEventSourcedRepository)
            ->build($aggregateCallingCommandHandler);

        $aggregateCommandHandler->handle(MessageBuilder::withPayload($commandToRun)->setReplyChannel($replyChannel)->build());

        $replyMessage = $replyChannel->receive();
        $this->assertEquals([new TicketWasStartedEvent(1)], $replyMessage->getPayload());
        $this->assertFalse($replyMessage->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_OBJECT));
        $this->assertFalse($replyMessage->getHeaders()->containsKey(AggregateMessage::RESULT_AGGREGATE_OBJECT));
    }

    public function test_calling_factory_method_for_event_sourced_aggregate_with_internal_recorder(): void
    {
        $commandToRun = new CreateAggregate(1);

        $aggregateCallingCommandHandler = CallAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(EventSourcingAggregateWithInternalRecorder::class)),
            'create',
            true,
            InterfaceToCallRegistry::createEmpty()
        )
            ->withInputChannelName('inputChannel');

        $replyChannel = QueueChannel::create();

        $aggregateCommandHandler = ComponentTestBuilder::create()->build($aggregateCallingCommandHandler);
        $aggregateCommandHandler->handle(MessageBuilder::withPayload($commandToRun)->setReplyChannel($replyChannel)->build());

        $aggregate = new EventSourcingAggregateWithInternalRecorder();
        $aggregate->recordThat(new AggregateCreated(1));

        $replyMessage = $replyChannel->receive();

        $this->assertEquals($aggregate, $replyMessage->getPayload());
        $this->assertFalse($replyMessage->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_OBJECT));
        $this->assertFalse($replyMessage->getHeaders()->containsKey(AggregateMessage::RESULT_AGGREGATE_OBJECT));
    }

    public function test_calling_action_method_for_existing_event_sourced_aggregate(): void
    {
        $ticketId = 1;
        $workerId = 100;
        $commandToRun = new AssignWorkerCommand($ticketId, $workerId);

        $aggregateCallingCommandHandler = CallAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Ticket::class)),
            'assignWorker',
            true,
            InterfaceToCallRegistry::createEmpty()
        )
            ->withInputChannelName('inputChannel');

        $queueChannel = QueueChannel::create();
        $inMemoryEventSourcedRepository = InMemoryEventSourcedRepository::createWithExistingAggregate(['ticketId' => $ticketId], Ticket::class, [new TicketWasStartedEvent($ticketId)]);

        $aggregateCommandHandler = ComponentTestBuilder::create()
            ->withReference('repository', $inMemoryEventSourcedRepository)
            ->build($aggregateCallingCommandHandler);

        $ticket = new Ticket();
        $ticket->onTicketWasStarted(new TicketWasStartedEvent($ticketId));

        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload($commandToRun)
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, clone $ticket)
                ->setReplyChannel($queueChannel)->build()
        );

        $workerWasAssignedEvent = new WorkerWasAssignedEvent($ticketId, $workerId);
        $replyMessage = $queueChannel->receive();
        $this->assertEquals([$workerWasAssignedEvent], $replyMessage->getPayload());
        $this->assertEquals($ticket, $replyMessage->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT));
    }

    public function test_calling_action_method_of_existing_event_sourcing_aggregate_with_internal_recorder(): void
    {
        $commandToRun = new DoSomething(1);

        $aggregateCallingCommandHandler = CallAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(EventSourcingAggregateWithInternalRecorder::class)),
            'doSomething',
            true,
            InterfaceToCallRegistry::createEmpty()
        )
            ->withInputChannelName('inputChannel');

        $replyChannel = QueueChannel::create();

        $aggregateCommandHandler = ComponentTestBuilder::create()
            ->build($aggregateCallingCommandHandler);

        $calledAggregate = new EventSourcingAggregateWithInternalRecorder();
        $calledAggregate->applyAggregateCreated(new AggregateCreated(1));

        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload($commandToRun)
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $calledAggregate)
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $calledAggregate->recordThat(new SomethingWasDone(1));

        $replyMessage = $replyChannel->receive();
        $this->assertEquals($commandToRun, $replyMessage->getPayload());
        $this->assertEquals($calledAggregate, $replyMessage->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT));
    }

    public function test_calling_action_method_of_existing_state_based_aggregate_with_internal_recorder(): void
    {
        $commandToRun = new DoSomething(1);

        $aggregateCallingCommandHandler = CallAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(StateBasedAggregateWithInternalRecorder::class)),
            'doSomething',
            true,
            InterfaceToCallRegistry::createEmpty()
        )
            ->withInputChannelName('inputChannel');

        $replyChannel = QueueChannel::create();

        $aggregateCommandHandler = ComponentTestBuilder::create()
            ->build($aggregateCallingCommandHandler);

        $calledAggregate = new StateBasedAggregateWithInternalRecorder(1);

        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload($commandToRun)
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $calledAggregate)
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $calledAggregate->recordThat(new SomethingWasDone(1));

        $replyMessage = $replyChannel->receive();
        $this->assertEquals($commandToRun, $replyMessage->getPayload());
        $this->assertEquals($calledAggregate, $replyMessage->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT));
    }

    public function test_calling_action_method_of_existing_event_sourcing_aggregate_with_internal_recorder_which_creates_another_aggregate(): void
    {
        $commandToRun = new CreateSomething(1, 1);

        $aggregateCallingCommandHandler = CallAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(EventSourcingAggregateWithInternalRecorder::class)),
            'createSomething',
            true,
            InterfaceToCallRegistry::createEmpty()
        )
            ->withInputChannelName('inputChannel');

        $replyChannel = QueueChannel::create();

        $aggregateCommandHandler = ComponentTestBuilder::create()
            ->build($aggregateCallingCommandHandler);

        $calledAggregate = new EventSourcingAggregateWithInternalRecorder();
        $calledAggregate->applyAggregateCreated(new AggregateCreated(1));

        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload($commandToRun)
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $calledAggregate)
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $calledAggregate->recordThat(new SomethingWasCreated(1, 1));

        $replyMessage = $replyChannel->receive();

        $this->assertEquals(new Something(1), $replyMessage->getPayload());
        $this->assertEquals($calledAggregate, $replyMessage->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT));
    }
}
