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
/**
 * licence Apache-2.0
 * @internal
 */
class CallAggregateServiceBuilderTest extends TestCase
{
    public function test_calling_existing_aggregate_method_with_command_class(): void
    {
        $aggregate                      = AggregateWithoutMessageClassesExample::create();

        $messaging = ComponentTestBuilder::create()
            ->withReference('repository', InMemoryStandardRepository::createEmpty())
            ->withMessageHandler(
                CallAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(AggregateWithoutMessageClassesExample::class)),
                    'doSomething',
                    true,
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertNull($aggregate->getChangedState());

        $messaging->sendMessageDirectToChannel(
            $inputChannel,
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

        $messaging = ComponentTestBuilder::create()
            ->withReference('orderRepository', InMemoryStandardRepository::createEmpty())
            ->withMessageHandler(
                CallAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Order::class)),
                    'getAmountWithQuery',
                    true,
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertEquals(
            $orderAmount,
            $messaging->sendMessageDirectToChannel(
                $inputChannel,
                MessageBuilder::withPayload(GetOrderAmountQuery::createWith(1))
                    ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $order)
                    ->build()
            )
        );
    }

    public function test_providing_correct_result_type_for_reply_message_when_result_is_collection(): void
    {
        $aggregateId = 1;
        $aggregate = Storage::create(new CreateStorage($aggregateId, [SmallBox::create(1)], []));

        $messaging = ComponentTestBuilder::create()
            ->withReference(InMemoryStandardRepository::class, InMemoryStandardRepository::createEmpty())
            ->withMessageHandler(
                CallAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Storage::class)),
                    'getSmallBoxes',
                    false,
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging
            ->sendDirectToChannelWithMessageReply(
                $inputChannel,
                MessageBuilder::withPayload(['storageId' => $aggregateId])
                    ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $aggregate)
                    ->build()
            );

        $this->assertEquals(
            TypeDescriptor::createCollection(SmallBox::class),
            $message->getHeaders()->getContentType()->getTypeParameter()
        );
    }

    public function test_providing_correct_result_type_for_reply_message_when_result_is_collection_of_unions(): void
    {
        $aggregateId = 1;
        $aggregate = Storage::create(new CreateStorage($aggregateId, [SmallBox::create(1)], [BigBox::create(2)]));

        $messaging = ComponentTestBuilder::create()
            ->withReference(InMemoryStandardRepository::class, InMemoryStandardRepository::createEmpty())
            ->withMessageHandler(
                CallAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Storage::class)),
                    'getBigBoxes',
                    false,
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendMessageDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload(['storageId' => $aggregateId])
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $aggregate)
                ->build()
        );

        $this->assertEquals(
            new UnionTypeDescriptor([
                TypeDescriptor::createCollection(Box::class),
                TypeDescriptor::createCollection(BigBox::class),
            ]),
            $message->getHeaders()->getContentType()->getTypeParameter()
        );
    }

    public function test_calling_aggregate_query_handler_returning_null_value(): void
    {
        $orderAmount = 5;
        $order = Order::createWith(CreateOrderCommand::createWith(1, $orderAmount, 'Poland'));
        $order->increaseAggregateVersion();

        $messaging = ComponentTestBuilder::create()
            ->withReference('orderRepository', InMemoryStandardRepository::createEmpty())
            ->withMessageHandler(
                CallAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Order::class)),
                    'getCustomerId',
                    false,
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertNull(
            $messaging
                ->sendMessageDirectToChannel(
                    $inputChannel,
                    MessageBuilder::withPayload(['orderId' => 1])
                        ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $order)
                        ->build()
                )
        );
    }

    public function test_calling_aggregate_for_query_handler_with_no_query(): void
    {
        $aggregate = AggregateWithoutMessageClassesExample::create(['id' => 1]);

        $messaging = ComponentTestBuilder::create()
            ->withReference('repository', InMemoryStandardRepository::createWith([$aggregate]))
            ->withMessageHandler(
                CallAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(AggregateWithoutMessageClassesExample::class)),
                    'querySomething',
                    false,
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertEquals(
            true,
            $messaging->sendDirectToChannel(
                $inputChannel,
                MessageBuilder::withPayload(['id' => 1])
                    ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $aggregate)
                    ->build()
            )
        );
    }

    public function test_calling_factory_method_for_event_sourced_aggregate(): void
    {
        $commandToRun = new StartTicketCommand(1);

        $inMemoryEventSourcedRepository = InMemoryEventSourcedRepository::createEmpty();

        $messaging = ComponentTestBuilder::create()
            ->withReference('repository', $inMemoryEventSourcedRepository)
            ->withMessageHandler(
                CallAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Ticket::class)),
                    'start',
                    true,
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $replyMessage = $messaging->sendMessageDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload($commandToRun)->build()
        );

        $this->assertEquals([new TicketWasStartedEvent(1)], $replyMessage->getPayload());
        $this->assertFalse($replyMessage->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_OBJECT));
        $this->assertFalse($replyMessage->getHeaders()->containsKey(AggregateMessage::RESULT_AGGREGATE_OBJECT));
    }

    public function test_calling_factory_method_for_event_sourced_aggregate_with_internal_recorder(): void
    {
        $commandToRun = new CreateAggregate(1);

        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                CallAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(EventSourcingAggregateWithInternalRecorder::class)),
                    'create',
                    true,
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $replyMessage = $messaging->sendDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload($commandToRun)->build()
        );

        $aggregate = new EventSourcingAggregateWithInternalRecorder();
        $aggregate->recordThat(new AggregateCreated(1));

        $this->assertEquals($aggregate, $replyMessage->getPayload());
        $this->assertFalse($replyMessage->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_OBJECT));
        $this->assertFalse($replyMessage->getHeaders()->containsKey(AggregateMessage::RESULT_AGGREGATE_OBJECT));
    }

    public function test_calling_action_method_for_existing_event_sourced_aggregate(): void
    {
        $ticketId = 1;
        $workerId = 100;
        $commandToRun = new AssignWorkerCommand($ticketId, $workerId);

        $queueChannel = QueueChannel::create();
        $inMemoryEventSourcedRepository = InMemoryEventSourcedRepository::createWithExistingAggregate(['ticketId' => $ticketId], Ticket::class, [new TicketWasStartedEvent($ticketId)]);

        $messaging = ComponentTestBuilder::create()
            ->withReference('repository', $inMemoryEventSourcedRepository)
            ->withMessageHandler(
                CallAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Ticket::class)),
                    'assignWorker',
                    true,
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $ticket = new Ticket();
        $ticket->onTicketWasStarted(new TicketWasStartedEvent($ticketId));

        $replyMessage = $messaging->sendDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload($commandToRun)
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, clone $ticket)
                ->build()
        );

        $workerWasAssignedEvent = new WorkerWasAssignedEvent($ticketId, $workerId);
        $this->assertEquals([$workerWasAssignedEvent], $replyMessage->getPayload());
        $this->assertEquals($ticket, $replyMessage->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT));
    }

    public function test_calling_action_method_of_existing_event_sourcing_aggregate_with_internal_recorder(): void
    {
        $commandToRun = new DoSomething(1);

        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                CallAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(EventSourcingAggregateWithInternalRecorder::class)),
                    'doSomething',
                    true,
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $calledAggregate = new EventSourcingAggregateWithInternalRecorder();
        $calledAggregate->applyAggregateCreated(new AggregateCreated(1));

        $replyMessage = $messaging->sendDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload($commandToRun)
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $calledAggregate)
                ->build()
        );

        $calledAggregate->recordThat(new SomethingWasDone(1));

        $this->assertEquals($commandToRun, $replyMessage->getPayload());
        $this->assertEquals($calledAggregate, $replyMessage->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT));
    }

    public function test_calling_action_method_of_existing_state_based_aggregate_with_internal_recorder(): void
    {
        $commandToRun = new DoSomething(1);

        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                CallAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(StateBasedAggregateWithInternalRecorder::class)),
                    'doSomething',
                    true,
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $calledAggregate = new StateBasedAggregateWithInternalRecorder(1);

        $replyMessage = $messaging->sendDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload($commandToRun)
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $calledAggregate)
                ->build()
        );

        $calledAggregate->recordThat(new SomethingWasDone(1));

        $this->assertEquals($commandToRun, $replyMessage->getPayload());
        $this->assertEquals($calledAggregate, $replyMessage->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT));
    }

    public function test_calling_action_method_of_existing_event_sourcing_aggregate_with_internal_recorder_which_creates_another_aggregate(): void
    {
        $commandToRun = new CreateSomething(1, 1);

        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                CallAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(EventSourcingAggregateWithInternalRecorder::class)),
                    'createSomething',
                    true,
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $calledAggregate = new EventSourcingAggregateWithInternalRecorder();
        $calledAggregate->applyAggregateCreated(new AggregateCreated(1));

        $replyMessage = $messaging->sendDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload($commandToRun)
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $calledAggregate)
                ->build()
        );

        $calledAggregate->recordThat(new SomethingWasCreated(1, 1));

        $this->assertEquals(new Something(1), $replyMessage->getPayload());
        $this->assertEquals($calledAggregate, $replyMessage->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT));
    }
}
