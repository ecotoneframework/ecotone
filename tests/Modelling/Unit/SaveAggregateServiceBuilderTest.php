<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Store\Document\DocumentStore;
use Ecotone\Messaging\Store\Document\InMemoryDocumentStore;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Config\BusModule;
use Ecotone\Modelling\Event;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\EventStream;
use Ecotone\Modelling\InMemoryEventSourcedRepository;
use Ecotone\Modelling\NoCorrectIdentifierDefinedException;
use Ecotone\Modelling\SaveAggregateService;
use Ecotone\Modelling\SaveAggregateServiceBuilder;
use Ecotone\Modelling\StandardRepository;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\Blog\Article;
use Test\Ecotone\Modelling\Fixture\Blog\PublishArticleCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\MultiplyAmountCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\Order;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\OrderWithManualVersioning;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory\NoIdDefinedAfterCallingFactoryExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\PublicIdentifierGetMethodForEventSourcedAggregate;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\PublicIdentifierGetMethodWithParameters;
use Test\Ecotone\Modelling\Fixture\Ticket\StartTicketCommand;
use Test\Ecotone\Modelling\Fixture\Ticket\Ticket;
use Test\Ecotone\Modelling\Fixture\Ticket\TicketWasStartedEvent;

/**
 * Class ServiceCallToAggregateAdapterTest
 * @package Test\Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SaveAggregateServiceBuilderTest extends TestCase
{
    public function test_saving_aggregate_method_with_only_command_as_parameter()
    {
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, "Poland"));

        $aggregateCallingCommandHandler = SaveAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Order::class)),
            "changeShippingAddress",
            InterfaceToCallRegistry::createEmpty(),
            SaveAggregateService::NO_SNAPSHOT_THRESHOLD,
            [],
            DocumentStore::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"]);

        $inMemoryStandardRepository = InMemoryStandardRepository::createEmpty();
        $aggregateCommandHandler    = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    BusModule::EVENT_CHANNEL_NAME_BY_OBJECT => QueueChannel::create(),
                    BusModule::EVENT_CHANNEL_NAME_BY_NAME => QueueChannel::create(),
                ]
            ),
            InMemoryReferenceSearchService::createWith(
                [
                    "orderRepository" => $inMemoryStandardRepository
                ]
            )
        );

        $this->assertNull($inMemoryStandardRepository->findBy(Order::class, ["orderId" => 1]));

        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload(CreateOrderCommand::createWith(1, 1, "Some"))
                ->setHeader(AggregateMessage::AGGREGATE_OBJECT, $order)
                ->build()
        );

        $this->assertEquals($order, $inMemoryStandardRepository->findBy(Order::class, ["orderId" => 1]));
    }

    public function test_snapshoting_aggregate_after_single_event()
    {
        $aggregateCallingCommandHandler = SaveAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Ticket::class)),
            "start",
            InterfaceToCallRegistry::createEmpty(),
            1,
            [Ticket::class],
            DocumentStore::class
        )
            ->withAggregateRepositoryFactories(["repository"]);

        $inMemoryDocumentStore = InMemoryDocumentStore::createEmpty();
        $aggregateCommandHandler    = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    BusModule::EVENT_CHANNEL_NAME_BY_OBJECT => QueueChannel::create(),
                    BusModule::EVENT_CHANNEL_NAME_BY_NAME => QueueChannel::create(),
                ]
            ),
            InMemoryReferenceSearchService::createWith(
                [
                    "repository" => InMemoryEventSourcedRepository::createEmpty(),
                    DocumentStore::class => $inMemoryDocumentStore
                ]
            )
        );

        $event = new TicketWasStartedEvent(1);
        $ticket = new Ticket();
        $ticket->onTicketWasStarted($event);
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload([$event])
                ->setHeader(AggregateMessage::AGGREGATE_ID, ["ticketId" => 1])
                ->setHeader(AggregateMessage::AGGREGATE_OBJECT, $ticket)
                ->setHeader(AggregateMessage::TARGET_VERSION, 0)
                ->build()
        );

        $this->assertEquals(
            $ticket,
            $inMemoryDocumentStore->getDocument(SaveAggregateService::getSnapshotCollectionName(Ticket::class), 1)
        );
    }

    public function test_skipping_snapshot_if_aggregate_not_registered_for_snapshoting()
    {
        $aggregateCallingCommandHandler = SaveAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Ticket::class)),
            "start",
            InterfaceToCallRegistry::createEmpty(),
            1,
            [Order::class],
            DocumentStore::class
        )
            ->withAggregateRepositoryFactories(["repository"]);

        $inMemoryDocumentStore = InMemoryDocumentStore::createEmpty();
        $aggregateCommandHandler    = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    BusModule::EVENT_CHANNEL_NAME_BY_OBJECT => QueueChannel::create(),
                    BusModule::EVENT_CHANNEL_NAME_BY_NAME => QueueChannel::create(),
                ]
            ),
            InMemoryReferenceSearchService::createWith(
                [
                    "repository" => InMemoryEventSourcedRepository::createEmpty(),
                    DocumentStore::class => $inMemoryDocumentStore
                ]
            )
        );

        $event = new TicketWasStartedEvent(1);
        $ticket = new Ticket();
        $ticket->onTicketWasStarted($event);
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload([$event])
                ->setHeader(AggregateMessage::AGGREGATE_ID, ["ticketId" => 1])
                ->setHeader(AggregateMessage::AGGREGATE_OBJECT, $ticket)
                ->setHeader(AggregateMessage::TARGET_VERSION, 0)
                ->build()
        );

        $this->assertEquals(0, $inMemoryDocumentStore->countDocuments(SaveAggregateService::getSnapshotCollectionName(Ticket::class)));
    }

    public function test_skipping_snapshot_if_not_desired_version_yet()
    {
        $aggregateCallingCommandHandler = SaveAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Ticket::class)),
            "start",
            InterfaceToCallRegistry::createEmpty(),
            2,
            [Ticket::class],
            DocumentStore::class
        )
            ->withAggregateRepositoryFactories(["repository"]);

        $inMemoryDocumentStore = InMemoryDocumentStore::createEmpty();
        $aggregateCommandHandler    = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    BusModule::EVENT_CHANNEL_NAME_BY_OBJECT => QueueChannel::create(),
                    BusModule::EVENT_CHANNEL_NAME_BY_NAME => QueueChannel::create(),
                ]
            ),
            InMemoryReferenceSearchService::createWith(
                [
                    "repository" => InMemoryEventSourcedRepository::createEmpty(),
                    DocumentStore::class => $inMemoryDocumentStore
                ]
            )
        );

        $event = new TicketWasStartedEvent(1);
        $ticket = new Ticket();
        $ticket->onTicketWasStarted($event);
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload([$event])
                ->setHeader(AggregateMessage::AGGREGATE_ID, ["ticketId" => 1])
                ->setHeader(AggregateMessage::AGGREGATE_OBJECT, $ticket)
                ->setHeader(AggregateMessage::TARGET_VERSION, 0)
                ->build()
        );

        $this->assertEquals(0, $inMemoryDocumentStore->countDocuments(SaveAggregateService::getSnapshotCollectionName(Ticket::class)));
    }

    public function test_returning_all_identifiers_assigned_during_aggregate_creation()
    {
        $publishArticle = PublishArticleCommand::createWith("johny", "Cat book", "Good content");

        $aggregateCallingCommandHandler = SaveAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Article::class)),
            "createWith",
            InterfaceToCallRegistry::createEmpty(),
            SaveAggregateService::NO_SNAPSHOT_THRESHOLD,
            [],
            DocumentStore::class
        )
            ->withAggregateRepositoryFactories(["repository"]);

        $inMemoryStandardRepository = InMemoryStandardRepository::createEmpty();
        $aggregateCommandHandler    = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    BusModule::EVENT_CHANNEL_NAME_BY_OBJECT => QueueChannel::create(),
                    BusModule::EVENT_CHANNEL_NAME_BY_NAME => QueueChannel::create(),
                ]
            ),
            InMemoryReferenceSearchService::createWith(
                [
                    "repository" => $inMemoryStandardRepository
                ]
            )
        );

        $replyChannel = QueueChannel::create();
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload($publishArticle)
                ->setHeader(AggregateMessage::AGGREGATE_OBJECT, Article::createWith($publishArticle))
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(["author" => "johny", "title" => "Cat book"], $replyChannel->receive()->getPayload());
    }

    public function test_calling_save_method_with_automatic_increasing_version()
    {
        $commandToRun = CreateOrderCommand::createWith(1, 1, "Poland");
        $order = Order::createWith($commandToRun);
        $order->increaseAggregateVersion();

        $orderRepository = $this->createMock(StandardRepository::class);
        $orderRepository->method("canHandle")
            ->with(Order::class)
            ->willReturn(true);
        $orderRepository->method("findBy")
            ->with(Order::class, ["orderId" => 1])
            ->willReturn($order);

        $newVersionOrder = clone $order;
        $newVersionOrder->increaseAggregateVersion();;
        $newVersionOrder->getRecordedEvents();

        $orderRepository->expects($this->once())
            ->method("save")
            ->with(
                ["orderId" => 1], $newVersionOrder, $this->callback(
                function () {
                    return true;
                }
            ), 1
            );

        $aggregateCallingCommandHandler = SaveAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Order::class)),
            "multiplyOrder",
            InterfaceToCallRegistry::createEmpty(),
            SaveAggregateService::NO_SNAPSHOT_THRESHOLD,
            [],
            DocumentStore::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    BusModule::EVENT_CHANNEL_NAME_BY_OBJECT => QueueChannel::create(),
                    BusModule::EVENT_CHANNEL_NAME_BY_NAME => QueueChannel::create(),
                ]
            ),
            InMemoryReferenceSearchService::createWith(
                [
                    "orderRepository" => $orderRepository,
                    ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
                ]
            )
        );

        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload($commandToRun)
                ->setHeader(AggregateMessage::AGGREGATE_OBJECT, $order)
                ->setHeader(AggregateMessage::TARGET_VERSION, 1)
                ->setReplyChannel(NullableMessageChannel::create())
                ->build()
        );
    }

    public function test_calling_save_method_with_manual_increasing_version()
    {
        $commandToRun = CreateOrderCommand::createWith(1, 1, "Poland");
        $order = OrderWithManualVersioning::createWith($commandToRun);
        $order->increaseAggregateVersion();

        $orderRepository = $this->createMock(StandardRepository::class);
        $orderRepository->method("canHandle")
            ->with(OrderWithManualVersioning::class)
            ->willReturn(true);
        $orderRepository->method("findBy")
            ->with(OrderWithManualVersioning::class, ["orderId" => 1])
            ->willReturn($order);

        $newVersionOrder = clone $order;
        $newVersionOrder->getRecordedEvents();

        $orderRepository->expects($this->once())
            ->method("save")
            ->with(
                ["orderId" => 1], $newVersionOrder, $this->callback(
                function () {
                    return true;
                }
            ), 1
            );

        $aggregateCallingCommandHandler = SaveAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(OrderWithManualVersioning::class)),
            "multiplyOrder",
            InterfaceToCallRegistry::createEmpty(),
            SaveAggregateService::NO_SNAPSHOT_THRESHOLD,
            [],
            DocumentStore::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    BusModule::EVENT_CHANNEL_NAME_BY_OBJECT => QueueChannel::create(),
                    BusModule::EVENT_CHANNEL_NAME_BY_NAME => QueueChannel::create(),
                ]
            ),
            InMemoryReferenceSearchService::createWith(
                [
                    "orderRepository" => $orderRepository,
                    ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
                ]
            )
        );

        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload($commandToRun)
                ->setHeader(AggregateMessage::AGGREGATE_OBJECT, $order)
                ->setHeader(AggregateMessage::TARGET_VERSION, 1)
                ->setReplyChannel(NullableMessageChannel::create())
                ->build()
        );
    }

    public function test_throwing_exception_if_aggregate_before_saving_has_no_nullable_identifier()
    {
        $aggregateCallingCommandHandler = SaveAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(NoIdDefinedAfterCallingFactoryExample::class)),
            "create",
            InterfaceToCallRegistry::createEmpty(),
            SaveAggregateService::NO_SNAPSHOT_THRESHOLD,
            [],
            DocumentStore::class
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    BusModule::EVENT_CHANNEL_NAME_BY_OBJECT => QueueChannel::create(),
                    BusModule::EVENT_CHANNEL_NAME_BY_NAME => QueueChannel::create(),
                ]
            ),
            InMemoryReferenceSearchService::createWith(
                [
                    "repository" => InMemoryEventSourcedRepository::createEmpty(),
                    ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
                ]
            )
        );

        $this->expectException(NoCorrectIdentifierDefinedException::class);

        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload([])
                ->setHeader(AggregateMessage::AGGREGATE_OBJECT, new NoIdDefinedAfterCallingFactoryExample())
                ->setReplyChannel(NullableMessageChannel::create())
                ->build()
        );
    }

    public function test_ignoring_aggregate_identifier_from_public_method_when_repository_save_called_directly_with_id()
    {
        $aggregateCallingCommandHandler = SaveAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(PublicIdentifierGetMethodForEventSourcedAggregate::class)),
            "create",
            InterfaceToCallRegistry::createEmpty(),
            SaveAggregateService::NO_SNAPSHOT_THRESHOLD,
            [],
            DocumentStore::class
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");

        $inMemoryEventSourcedRepository = InMemoryEventSourcedRepository::createEmpty();

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    BusModule::EVENT_CHANNEL_NAME_BY_OBJECT => QueueChannel::create(),
                    BusModule::EVENT_CHANNEL_NAME_BY_NAME => QueueChannel::create(),
                ]
            ),
            InMemoryReferenceSearchService::createWith(
                [
                    "repository" => $inMemoryEventSourcedRepository
                ]
            )
        );

        $aggregateId = ["id" => 123];
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload([new \stdClass()])
                ->setHeader(AggregateMessage::AGGREGATE_OBJECT, new PublicIdentifierGetMethodForEventSourcedAggregate())
                ->setHeader(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER, 123)
                ->setHeader(AggregateMessage::AGGREGATE_ID, $aggregateId)
                ->setHeader(AggregateMessage::TARGET_VERSION, 0)
                ->setReplyChannel(NullableMessageChannel::create())
                ->build()
        );

        $this->assertEquals(
            new \stdClass(),
            $inMemoryEventSourcedRepository->findBy(PublicIdentifierGetMethodForEventSourcedAggregate::class, $aggregateId)->getEvents()[0]->getEvent()
        );
    }

    public function test_throwing_exception_if_aggregate_identifier_getter_has_parameters()
    {
        $this->expectException(NoCorrectIdentifierDefinedException::class);

        SaveAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(PublicIdentifierGetMethodWithParameters::class)),
            "create",
            InterfaceToCallRegistry::createEmpty(),
            SaveAggregateService::NO_SNAPSHOT_THRESHOLD,
            [],
            DocumentStore::class
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");
    }
}