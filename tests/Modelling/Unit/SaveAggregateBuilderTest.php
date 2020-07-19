<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\SaveAggregateServiceBuilder;
use Ecotone\Modelling\AggregateNotFoundException;
use Ecotone\Modelling\InMemoryEventSourcedRepository;
use Ecotone\Modelling\NoCorrectIdentifierDefinedException;
use Ecotone\Modelling\StandardRepository;
use Ecotone\Modelling\AggregateVersionMismatchException;
use Ecotone\Modelling\LazyEventBus\LazyEventBus;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateWithoutMessageClassesExample;
use Test\Ecotone\Modelling\Fixture\Blog\Article;
use Test\Ecotone\Modelling\Fixture\Blog\ChangeArticleContentCommand;
use Test\Ecotone\Modelling\Fixture\Blog\CloseArticleCommand;
use Test\Ecotone\Modelling\Fixture\Blog\InMemoryArticleStandardRepository;
use Test\Ecotone\Modelling\Fixture\Blog\PublishArticleCommand;
use Test\Ecotone\Modelling\Fixture\Blog\PublishArticleWithTitleOnlyCommand;
use Test\Ecotone\Modelling\Fixture\Blog\RepublishArticleCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\ChangeShippingAddressCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\CommandWithoutAggregateIdentifier;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\GetOrderAmountQuery;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\GetShippingAddressQuery;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\MultiplyAmountCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\Order;
use Test\Ecotone\Modelling\Fixture\Handler\ReplyViaHeadersMessageHandler;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\FactoryMethodWithWrongParameterCountExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\IncorrectEventTypeReturned\CreateIncorrectEventTypeReturnedAggregate;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\IncorrectEventTypeReturned\IncorrectEventTypeReturnedExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoFactoryMethodAggregateExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory\CreateNoIdDefinedAggregate;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory\NoIdDefinedAfterCallingFactoryExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NonStaticFactoryMethodExample;
use Test\Ecotone\Modelling\Fixture\Saga\OrderFulfilment;
use Test\Ecotone\Modelling\Fixture\Saga\PaymentWasDoneEvent;
use Test\Ecotone\Modelling\Fixture\Ticket\AssignWorkerCommand;
use Test\Ecotone\Modelling\Fixture\Ticket\StartTicketCommand;
use Test\Ecotone\Modelling\Fixture\Ticket\Ticket;
use Test\Ecotone\Modelling\Fixture\Ticket\TicketWasStartedEvent;
use Test\Ecotone\Modelling\Fixture\Ticket\WorkerAssignationFailedEvent;
use Test\Ecotone\Modelling\Fixture\Ticket\WorkerWasAssignedEvent;

/**
 * Class ServiceCallToAggregateAdapterTest
 * @package Test\Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SaveAggregateBuilderTest extends TestCase
{
    public function test_saving_aggregate_method_with_only_command_as_parameter()
    {
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, "Poland"));

        $aggregateCallingCommandHandler = SaveAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Order::class)),
            "changeShippingAddress"
        )
            ->withAggregateRepositoryFactories(["orderRepository"]);

        $inMemoryStandardRepository = InMemoryStandardRepository::createEmpty();
        $aggregateCommandHandler    = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                EventBus::CHANNEL_NAME_BY_OBJECT => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "orderRepository" => $inMemoryStandardRepository
            ])
        );

        $this->assertNull($inMemoryStandardRepository->findBy(Order::class, ["orderId" => 1]));

        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload(CreateOrderCommand::createWith(1, 1, "Some"))
                ->setHeader(AggregateMessage::AGGREGATE_OBJECT, $order)
                ->build()
        );

        $this->assertEquals($order, $inMemoryStandardRepository->findBy(Order::class, ["orderId" => 1]));
    }

    public function test_calling_save_method_with_next_version()
    {
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, "Poland"));
        $order->increaseAggregateVersion();

        $commandToRun = MultiplyAmountCommand::create(1, 1, 10);

        $orderRepository = $this->createMock(StandardRepository::class);
        $orderRepository->method("canHandle")
            ->with(Order::class)
            ->willReturn(true);
        $orderRepository->method("findBy")
            ->with(Order::class, ["orderId" => 1])
            ->willReturn($order);

        $order->multiplyOrder($commandToRun);
        $orderRepository->expects($this->once())
            ->method("save")
            ->with(["orderId" => 1], $order, $this->callback(function(){return true;}), 2);

        $aggregateCallingCommandHandler = SaveAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Order::class)),
            "multiplyOrder"
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                EventBus::CHANNEL_NAME_BY_OBJECT => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "orderRepository" => $orderRepository,
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload($commandToRun)
                ->setHeader(AggregateMessage::AGGREGATE_OBJECT, $order)
                ->setReplyChannel(NullableMessageChannel::create())
                ->build()
        );
    }

    public function test_throwing_exception_if_aggregate_before_saving_has_no_nullable_identifier()
    {
        $aggregateCallingCommandHandler = SaveAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(NoIdDefinedAfterCallingFactoryExample::class)),
            "create"
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                EventBus::CHANNEL_NAME_BY_OBJECT => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryEventSourcedRepository::createEmpty(),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $this->expectException(NoCorrectIdentifierDefinedException::class);

        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload([])
                ->setHeader(AggregateMessage::AGGREGATE_OBJECT, new NoIdDefinedAfterCallingFactoryExample())
                ->setReplyChannel(NullableMessageChannel::create())
                ->build()
        );
    }
}