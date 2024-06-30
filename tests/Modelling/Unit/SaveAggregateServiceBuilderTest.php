<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Store\Document\DocumentStore;
use Ecotone\Messaging\Store\Document\InMemoryDocumentStore;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\SaveAggregateServiceBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\SaveEventSourcingAggregateService;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\BaseEventSourcingConfiguration;
use Ecotone\Modelling\InMemoryEventSourcedRepository;
use Ecotone\Modelling\NoCorrectIdentifierDefinedException;
use Ecotone\Modelling\StandardRepository;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\Modelling\Fixture\Blog\Article;
use Test\Ecotone\Modelling\Fixture\Blog\PublishArticleCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\Order;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\OrderWithManualVersioning;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory\NoIdDefinedAfterRecordingEvents;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\PublicIdentifierGetMethodForEventSourcedAggregate;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\PublicIdentifierGetMethodWithParameters;
use Test\Ecotone\Modelling\Fixture\Ticket\Ticket;
use Test\Ecotone\Modelling\Fixture\Ticket\TicketWasStartedEvent;

/**
 * Class ServiceCallToAggregateAdapterTest
 * @package Test\Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class SaveAggregateServiceBuilderTest extends TestCase
{
    public function test_saving_aggregate_method_with_only_command_as_parameter()
    {
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, 'Poland'));

        $inMemoryStandardRepository = InMemoryStandardRepository::createEmpty();
        $messaging    = ComponentTestBuilder::create()
            ->withReference('orderRepository', $inMemoryStandardRepository)
            ->withMessageHandler(
                SaveAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Order::class)),
                    'changeShippingAddress',
                    InterfaceToCallRegistry::createEmpty(),
                    (new BaseEventSourcingConfiguration())
                )
                    ->withAggregateRepositoryFactories(['orderRepository'])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertNull($inMemoryStandardRepository->findBy(Order::class, ['orderId' => 1]));

        $messaging->sendMessageDirectToChannel(
            $inputChannel,
            MessageBuilder::withPayload(CreateOrderCommand::createWith(1, 1, 'Some'))
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $order)
                ->setHeader(AggregateMessage::RESULT_AGGREGATE_OBJECT, $order)
                ->build()
        );

        $this->assertEquals($order, $inMemoryStandardRepository->findBy(Order::class, ['orderId' => 1]));
    }

    public function test_snapshoting_aggregate_after_single_event()
    {
        $inMemoryDocumentStore = InMemoryDocumentStore::createEmpty();
        $aggregateCommandHandler = ComponentTestBuilder::create()
            ->withReference('repository', InMemoryEventSourcedRepository::createEmpty())
            ->withReference(DocumentStore::class, $inMemoryDocumentStore)
            ->withMessageHandler(
                SaveAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Ticket::class)),
                    'start',
                    InterfaceToCallRegistry::createEmpty(),
                    (new BaseEventSourcingConfiguration())->withSnapshotsFor(Ticket::class, 1)
                )
                    ->withAggregateRepositoryFactories(['repository'])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $event = new TicketWasStartedEvent(1);
        $ticket = new Ticket();
        $ticket->onTicketWasStarted($event);
        $aggregateCommandHandler->sendMessageDirectToChannel(
            $inputChannel,
            MessageBuilder::withPayload([$event])
                ->setHeader(AggregateMessage::AGGREGATE_ID, ['ticketId' => 1])
                ->setHeader(AggregateMessage::RESULT_AGGREGATE_OBJECT, $ticket)
                ->setHeader(AggregateMessage::TARGET_VERSION, 0)
                ->setHeader(AggregateMessage::RESULT_AGGREGATE_EVENTS, [$event])
                ->build()
        );

        $this->assertEquals(
            $ticket,
            $inMemoryDocumentStore->getDocument(SaveEventSourcingAggregateService::getSnapshotCollectionName(Ticket::class), 1)
        );
    }

    public function test_skipping_snapshot_if_aggregate_not_registered_for_snapshoting()
    {
        $inMemoryDocumentStore = InMemoryDocumentStore::createEmpty();
        $aggregateCommandHandler = ComponentTestBuilder::create()
            ->withReference('repository', InMemoryEventSourcedRepository::createEmpty())
            ->withReference(DocumentStore::class, $inMemoryDocumentStore)
            ->withMessageHandler(
                SaveAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Ticket::class)),
                    'start',
                    InterfaceToCallRegistry::createEmpty(),
                    (new BaseEventSourcingConfiguration())
                )
                    ->withAggregateRepositoryFactories(['repository'])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $event = new TicketWasStartedEvent(1);
        $ticket = new Ticket();
        $ticket->onTicketWasStarted($event);
        $aggregateCommandHandler->sendMessageDirectToChannel(
            $inputChannel,
            MessageBuilder::withPayload([$event])
                ->setHeader(AggregateMessage::AGGREGATE_ID, ['ticketId' => 1])
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $ticket)
                ->setHeader(AggregateMessage::RESULT_AGGREGATE_OBJECT, $ticket)
                ->setHeader(AggregateMessage::TARGET_VERSION, 0)
                ->build()
        );

        $this->assertEquals(0, $inMemoryDocumentStore->countDocuments(SaveEventSourcingAggregateService::getSnapshotCollectionName(Ticket::class)));
    }

    public function test_skipping_snapshot_if_not_desired_version_yet()
    {
        $inMemoryDocumentStore = InMemoryDocumentStore::createEmpty();
        $aggregateCommandHandler = ComponentTestBuilder::create()
            ->withReference('repository', InMemoryEventSourcedRepository::createEmpty())
            ->withReference(DocumentStore::class, $inMemoryDocumentStore)
            ->withMessageHandler(
                SaveAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Ticket::class)),
                    'start',
                    InterfaceToCallRegistry::createEmpty(),
                    (new BaseEventSourcingConfiguration())->withSnapshotsFor(Ticket::class, 2)
                )
                    ->withAggregateRepositoryFactories(['repository'])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $event = new TicketWasStartedEvent(1);
        $ticket = new Ticket();
        $ticket->onTicketWasStarted($event);
        $aggregateCommandHandler->sendMessageDirectToChannel(
            $inputChannel,
            MessageBuilder::withPayload([$event])
                ->setHeader(AggregateMessage::AGGREGATE_ID, ['ticketId' => 1])
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $ticket)
                ->setHeader(AggregateMessage::RESULT_AGGREGATE_OBJECT, $ticket)
                ->setHeader(AggregateMessage::TARGET_VERSION, 0)
                ->build()
        );

        $this->assertEquals(0, $inMemoryDocumentStore->countDocuments(SaveEventSourcingAggregateService::getSnapshotCollectionName(Ticket::class)));
    }

    public function test_returning_all_identifiers_assigned_during_aggregate_creation()
    {
        $publishArticle = PublishArticleCommand::createWith('johny', 'Cat book', 'Good content');

        $inMemoryStandardRepository = InMemoryStandardRepository::createEmpty();
        $messaging = ComponentTestBuilder::create()
            ->withReference('repository', $inMemoryStandardRepository)
            ->withMessageHandler(
                SaveAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Article::class)),
                    'createWith',
                    InterfaceToCallRegistry::createEmpty(),
                    (new BaseEventSourcingConfiguration())
                )
                    ->withAggregateRepositoryFactories(['repository'])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $replyChannel = QueueChannel::create();
        $reply = $messaging->sendMessageDirectToChannel(
            $inputChannel,
            MessageBuilder::withPayload($publishArticle)
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, Article::createWith($publishArticle))
                ->setHeader(AggregateMessage::RESULT_AGGREGATE_OBJECT, Article::createWith($publishArticle))
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(['author' => 'johny', 'title' => 'Cat book'], $reply);
    }

    public function test_calling_save_method_with_automatic_increasing_version()
    {
        $commandToRun = CreateOrderCommand::createWith(1, 1, 'Poland');
        $order = Order::createWith($commandToRun);
        $order->increaseAggregateVersion();

        $orderRepository = $this->createMock(StandardRepository::class);
        $orderRepository->method('canHandle')
            ->with(Order::class)
            ->willReturn(true);
        $orderRepository->method('findBy')
            ->with(Order::class, ['orderId' => 1])
            ->willReturn($order);

        $newVersionOrder = clone $order;
        $newVersionOrder->increaseAggregateVersion();

        $orderRepository->expects($this->once())
            ->method('save')
            ->with(
                ['orderId' => 1],
                $newVersionOrder,
                $this->callback(
                    function () {
                        return true;
                    }
                ),
                1
            );

        $messaging = ComponentTestBuilder::create()
            ->withReference('orderRepository', $orderRepository)
            ->withMessageHandler(
                SaveAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Order::class)),
                    'multiplyOrder',
                    InterfaceToCallRegistry::createEmpty(),
                    (new BaseEventSourcingConfiguration())
                )
                    ->withAggregateRepositoryFactories(['orderRepository'])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $messaging->sendMessageDirectToChannel(
            $inputChannel,
            MessageBuilder::withPayload($commandToRun)
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $order)
                ->setHeader(AggregateMessage::RESULT_AGGREGATE_OBJECT, $order)
                ->setHeader(AggregateMessage::TARGET_VERSION, 1)
                ->setReplyChannel(NullableMessageChannel::create())
                ->build()
        );
    }

    public function test_calling_save_method_with_manual_increasing_version()
    {
        $commandToRun = CreateOrderCommand::createWith(1, 1, 'Poland');
        $order = OrderWithManualVersioning::createWith($commandToRun);
        $order->increaseAggregateVersion();

        $orderRepository = $this->createMock(StandardRepository::class);
        $orderRepository->method('canHandle')
            ->with(OrderWithManualVersioning::class)
            ->willReturn(true);
        $orderRepository->method('findBy')
            ->with(OrderWithManualVersioning::class, ['orderId' => 1])
            ->willReturn($order);

        $newVersionOrder = clone $order;

        $orderRepository->expects($this->once())
            ->method('save')
            ->with(
                ['orderId' => 1],
                $newVersionOrder,
                $this->callback(
                    function () {
                        return true;
                    }
                ),
                1
            );

        $messaging = ComponentTestBuilder::create()
            ->withReference('orderRepository', $orderRepository)
            ->withMessageHandler(
                SaveAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(OrderWithManualVersioning::class)),
                    'multiplyOrder',
                    InterfaceToCallRegistry::createEmpty(),
                    (new BaseEventSourcingConfiguration())
                )
                    ->withAggregateRepositoryFactories(['orderRepository'])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $messaging->sendMessageDirectToChannel(
            $inputChannel,
            MessageBuilder::withPayload($commandToRun)
                ->setHeader(AggregateMessage::CALLED_AGGREGATE_OBJECT, $order)
                ->setHeader(AggregateMessage::RESULT_AGGREGATE_OBJECT, $order)
                ->setHeader(AggregateMessage::TARGET_VERSION, 1)
                ->setReplyChannel(NullableMessageChannel::create())
                ->build()
        );
    }

    public function test_throwing_exception_if_aggregate_before_saving_has_no_nullable_identifier()
    {
        $aggregateCommandHandler = ComponentTestBuilder::create()
            ->withReference('repository', InMemoryEventSourcedRepository::createEmpty())
            ->withMessageHandler(
                SaveAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(NoIdDefinedAfterRecordingEvents::class)),
                    'create',
                    InterfaceToCallRegistry::createEmpty(),
                    (new BaseEventSourcingConfiguration())
                )
                    ->withAggregateRepositoryFactories(['repository'])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->expectException(NoCorrectIdentifierDefinedException::class);

        $aggregateCommandHandler->sendMessageDirectToChannel(
            $inputChannel,
            MessageBuilder::withPayload([])
                ->setHeader(AggregateMessage::RESULT_AGGREGATE_OBJECT, new NoIdDefinedAfterRecordingEvents())
                ->setHeader(AggregateMessage::RESULT_AGGREGATE_EVENTS, [new stdClass()])
                ->setReplyChannel(NullableMessageChannel::create())
                ->build()
        );
    }

    public function test_ignoring_aggregate_identifier_from_public_method_when_repository_save_called_directly_with_id()
    {
        $inMemoryEventSourcedRepository = InMemoryEventSourcedRepository::createEmpty();

        $aggregateCommandHandler = ComponentTestBuilder::create()
            ->withReference('repository', $inMemoryEventSourcedRepository)
            ->withMessageHandler(
                SaveAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(PublicIdentifierGetMethodForEventSourcedAggregate::class)),
                    'create',
                    InterfaceToCallRegistry::createEmpty(),
                    (new BaseEventSourcingConfiguration())
                )
                    ->withAggregateRepositoryFactories(['repository'])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $aggregateId = ['id' => 123];
        $aggregateCommandHandler->sendMessageDirectToChannel(
            $inputChannel,
            MessageBuilder::withPayload([new stdClass()])
                ->setHeader(AggregateMessage::RESULT_AGGREGATE_OBJECT, new PublicIdentifierGetMethodForEventSourcedAggregate())
                ->setHeader(AggregateMessage::RESULT_AGGREGATE_EVENTS, [new stdClass()])
                ->setHeader(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER, 123)
                ->setHeader(AggregateMessage::AGGREGATE_ID, $aggregateId)
                ->setHeader(AggregateMessage::TARGET_VERSION, 0)
                ->setReplyChannel(NullableMessageChannel::create())
                ->build()
        );

        $this->assertEquals(
            new stdClass(),
            $inMemoryEventSourcedRepository->findBy(PublicIdentifierGetMethodForEventSourcedAggregate::class, $aggregateId)->getEvents()[0]->getPayload()
        );
    }

    public function test_throwing_exception_if_aggregate_identifier_getter_has_parameters()
    {
        $this->expectException(NoCorrectIdentifierDefinedException::class);

        ComponentTestBuilder::create()
            ->withMessageHandler(
                SaveAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(PublicIdentifierGetMethodWithParameters::class)),
                    'create',
                    InterfaceToCallRegistry::createEmpty(),
                    (new BaseEventSourcingConfiguration())
                )
                    ->withAggregateRepositoryFactories(['repository'])
                    ->withInputChannelName('inputChannel')
            )
            ->build()
        ;
    }
}
