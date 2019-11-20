<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessageHandlerBuilder;
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
use Test\Ecotone\Modelling\Fixture\Blog\InMemoryArticleRepositoryFactory;
use Test\Ecotone\Modelling\Fixture\Blog\PublishArticleCommand;
use Test\Ecotone\Modelling\Fixture\Blog\PublishArticleWithTitleOnlyCommand;
use Test\Ecotone\Modelling\Fixture\Blog\RepublishArticleCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\ChangeShippingAddressCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\CommandWithoutAggregateIdentifier;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\GetOrderAmountQuery;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryOrderAggregateRepositoryConstructor;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryOrderRepositoryFactory;
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
use Test\Ecotone\Modelling\Fixture\TestingEventBus;
use Test\Ecotone\Modelling\Fixture\TestingLazyEventBus;
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
class AggregateMessageHandlerBuilderTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_configuring_command_handler()
    {
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Order::class,
            "changeShippingAddress",
            ChangeShippingAddressCommand::class
        )->withInputChannelName(ChangeShippingAddressCommand::class);

        $this->assertEquals(ChangeShippingAddressCommand::class, $aggregateCallingCommandHandler->getInputMessageChannelName());
        $this->assertEquals([], $aggregateCallingCommandHandler->getRequiredReferenceNames());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_existing_aggregate_method_with_only_command_as_parameter()
    {
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, "Poland"));
        $order->increaseAggregateVersion();

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Order::class,
            "changeShippingAddress",
            ChangeShippingAddressCommand::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"]);

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "orderRepository" => InMemoryStandardRepository::createWith([$order]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $newShippingAddress = "Germany";
        $aggregateCommandHandler->handle(MessageBuilder::withPayload(ChangeShippingAddressCommand::create(1, 1, $newShippingAddress))->setReplyChannel(NullableMessageChannel::create())->build());

        $this->assertEquals($newShippingAddress, $order->getShippingAddress());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_existing_aggregate_method_with_command_and_no_command()
    {
        $aggregate = AggregateWithoutMessageClassesExample::create(["id" => 1]);
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            AggregateWithoutMessageClassesExample::class,
            "doSomething",
            null
        )
            ->withAggregateRepositoryFactories(["repository"]);

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryStandardRepository::createWith([$aggregate]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(["id" => 1])->build());

        $this->assertTrue($aggregate->getChangedState());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_existing_aggregate_method_with_command_as_array()
    {
        $aggregate = AggregateWithoutMessageClassesExample::create(["id" => 1]);
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            AggregateWithoutMessageClassesExample::class,
            "doSomethingWithData",
            null
        )
            ->withAggregateRepositoryFactories(["repository"]);

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryStandardRepository::createWith([$aggregate]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $data = ["id" => 1, "someData" => 100];
        $aggregateCommandHandler->handle(MessageBuilder::withPayload($data)->build());

        $this->assertEquals($data, $aggregate->getChangedState());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_existing_aggregate_method_with_no_command_data_and_reference()
    {
        $aggregate = AggregateWithoutMessageClassesExample::create(["id" => 1]);
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            AggregateWithoutMessageClassesExample::class,
            "doSomethingWithReference",
            null
        )
            ->withMethodParameterConverters([
                ReferenceBuilder::create("class", \stdClass::class)
            ])
            ->withAggregateRepositoryFactories(["repository"]);

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryStandardRepository::createWith([$aggregate]),
                \stdClass::class => new \stdClass(),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(["id" => 1])->build());

        $this->assertTrue($aggregate->getChangedState());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_throwing_exception_if_no_id_found_in_command()
    {
        $aggregate = AggregateWithoutMessageClassesExample::create(["id" => 1]);
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            AggregateWithoutMessageClassesExample::class,
            "doSomething",
            null
        )
            ->withAggregateRepositoryFactories(["repository"]);

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryStandardRepository::createWith([$aggregate]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $this->expectException(AggregateNotFoundException::class);

        $aggregateCommandHandler->handle(MessageBuilder::withPayload([])->build());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_aggregate_for_query_handler_with_return_value()
    {
        $orderAmount = 5;
        $order = Order::createWith(CreateOrderCommand::createWith(1, $orderAmount, "Poland"));
        $order->increaseAggregateVersion();

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith(
            Order::class,
            "getAmountWithQuery",
            GetOrderAmountQuery::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"]);

        $aggregateQueryHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "orderRepository" => InMemoryStandardRepository::createWith([$order]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $aggregateQueryHandler->handle(
            MessageBuilder::withPayload(GetOrderAmountQuery::createWith(1))
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(
            $orderAmount,
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_aggregate_query_handler_returning_null_value()
    {
        $orderAmount = 5;
        $order = Order::createWith(CreateOrderCommand::createWith(1, $orderAmount, "Poland"));
        $order->increaseAggregateVersion();

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith(
            Order::class,
            "getCustomerId",
            null
        )
            ->withAggregateRepositoryFactories(["orderRepository"]);

        $aggregateQueryHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "orderRepository" => InMemoryStandardRepository::createWith([$order]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $aggregateQueryHandler->handle(
            MessageBuilder::withPayload(["orderId" => 1])
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertNull($replyChannel->receive());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_aggregate_for_query_handler_with_no_query()
    {
        $aggregate = AggregateWithoutMessageClassesExample::create(["id" => 1]);
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith(
            AggregateWithoutMessageClassesExample::class,
            "querySomething",
            null
        )
            ->withAggregateRepositoryFactories(["repository"]);

        $aggregateQueryHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryStandardRepository::createWith([$aggregate]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $aggregateQueryHandler->handle(
            MessageBuilder::withPayload(["id" => 1])
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(
            true,
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_aggregate_for_query_handler_with_query_as_array()
    {
        $aggregate = AggregateWithoutMessageClassesExample::create(["id" => 1]);
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith(
            AggregateWithoutMessageClassesExample::class,
            "querySomethingWithData",
            null
        )
            ->withAggregateRepositoryFactories(["repository"]);

        $aggregateQueryHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryStandardRepository::createWith([$aggregate]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $payload = ["id" => 1, "some" => 123];
        $aggregateQueryHandler->handle(
            MessageBuilder::withPayload($payload)
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(
            $payload,
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_aggregate_for_query_handler_with_output_channel()
    {
        $outputChannelName = "output";
        $orderAmount = 5;
        $order = Order::createWith(CreateOrderCommand::createWith(1, $orderAmount, "Poland"));
        $order->increaseAggregateVersion();

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith(
            Order::class,
            "getAmountWithQuery",
            GetOrderAmountQuery::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel")
            ->withOutputMessageChannel($outputChannelName);

        $outputChannel = QueueChannel::create();
        $aggregateQueryHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    $outputChannelName => $outputChannel,
                    LazyEventBus::CHANNEL_NAME => QueueChannel::create()
                ]
            ),
            InMemoryReferenceSearchService::createWith([
                "orderRepository" => InMemoryStandardRepository::createWith([$order]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $aggregateQueryHandler->handle(
            MessageBuilder::withPayload(GetOrderAmountQuery::createWith(1))
                ->build()
        );

        $this->assertEquals(
            $orderAmount,
            $outputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_aggregate_for_query_without_parameters()
    {
        $orderAmount = 5;
        $order = Order::createWith(CreateOrderCommand::createWith(1, $orderAmount, "Poland"));
        $order->increaseAggregateVersion();

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith(
            Order::class,
            "getAmount",
            GetOrderAmountQuery::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateQueryHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "orderRepository" => InMemoryStandardRepository::createWith([$order]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $aggregateQueryHandler->handle(
            MessageBuilder::withPayload(GetOrderAmountQuery::createWith(1))
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(
            $orderAmount,
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_creating_new_aggregate_from_factory_method()
    {
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Order::class,
            "createWith",
            CreateOrderCommand::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "orderRepository" => InMemoryStandardRepository::createEmpty(),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload(CreateOrderCommand::createWith(1, 1, "Poland"))
                ->setReplyChannel($replyChannel)
                ->build()
        );


        $this->assertEquals(["orderId" => 1], $replyChannel->receive()->getPayload());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_redirect_to_channel_on_factory_creation_already_found()
    {
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, "Poland"));
        $order->increaseAggregateVersion();

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Order::class,
            "createWith",
            CreateOrderCommand::class
        )
            ->withMethodParameterConverters([
                PayloadBuilder::create("command")
            ])
            ->withRedirectToOnAlreadyExists("increaseAmount", [
                PayloadBuilder::create("command")
            ])
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "orderRepository" => InMemoryStandardRepository::createWith([
                    $order
                ]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload(CreateOrderCommand::createWith(1, 1, "Poland"))
                ->setReplyChannel($replyChannel)
                ->build()
        );


        $this->assertEquals(2, $order->getAmount());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_handling_aggregate_factory_normally_when_aggregate_not_found_for_redirect()
    {
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Order::class,
            "createWith",
            CreateOrderCommand::class
        )
            ->withMethodParameterConverters([
                PayloadBuilder::create("command")
            ])
            ->withRedirectToOnAlreadyExists("increaseAmount", [
                PayloadBuilder::create("command")
            ])
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "orderRepository" => InMemoryStandardRepository::createEmpty(),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload(CreateOrderCommand::createWith(1, 1, "Poland"))
                ->setReplyChannel($replyChannel)
                ->build()
        );


        $this->assertEquals(["orderId" => 1], $replyChannel->receive()->getPayload());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_creating_new_aggregate_from_factory_method_with_two_identifiers()
    {
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Article::class,
            "createWith",
            PublishArticleCommand::class
        )
            ->withAggregateRepositoryFactories(["articleRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "articleRepository" => InMemoryArticleStandardRepository::createEmpty(),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload(PublishArticleCommand::createWith("johny", "Tolkien", "some bla bla"))
                ->setReplyChannel($replyChannel)
                ->build()
        );


        $this->assertEquals(["author" => "johny", "title" => "Tolkien"], $replyChannel->receive()->getPayload());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_creating_new_aggregate_with_command_as_array_type()
    {
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            AggregateWithoutMessageClassesExample::class,
            "createWithData",
            null
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("createAggregate");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryStandardRepository::createEmpty(),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload(["id" => 1])
                ->setReplyChannel($replyChannel)
                ->build()
        );


        $this->assertEquals(["id" => 1], $replyChannel->receive()->getPayload());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_creating_new_aggregate_with_no_command_data()
    {
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            AggregateWithoutMessageClassesExample::class,
            "create",
            null
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("createAggregate");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryStandardRepository::createEmpty(),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload([])
                ->setReplyChannel($replyChannel)
                ->build()
        );


        $this->assertEquals(["id" => 1], $replyChannel->receive()->getPayload());
    }



    /**
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_aggregate_with_no_identifiers_defined_inside_command()
    {
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Article::class,
            "changeContent",
            ChangeArticleContentCommand::class
        )
            ->withAggregateRepositoryFactories(["articleRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "articleRepository" => InMemoryArticleStandardRepository::createWith([
                    Article::createWith(PublishArticleCommand::createWith("johny", "Tolkien", "some bla bla"))
                ]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $aggregateCommandHandler->handle(MessageBuilder::withPayload(ChangeArticleContentCommand::createWith("johny", "Tolkien", "new content"))->setReplyChannel($replyChannel)->build());


        $this->assertEquals(true, $replyChannel->receive()->getPayload());
    }

    /**
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_aggregate_with_target_identifiers()
    {
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Article::class,
            "close",
            CloseArticleCommand::class
        )
            ->withAggregateRepositoryFactories(["articleRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "articleRepository" => InMemoryArticleStandardRepository::createWith([
                    Article::createWith(PublishArticleCommand::createWith("johny", "Tolkien", "some bla bla"))
                ]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $command = CloseArticleCommand::createWith("johny", "Tolkien");
        $aggregateCommandHandler->handle(MessageBuilder::withPayload($command)->setReplyChannel($replyChannel)->build());


        $this->assertEquals($command, $replyChannel->receive()->getPayload());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_aggregate_with_version_locking_from_command()
    {
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, "Poland"));
        $order->increaseAggregateVersion();

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Order::class,
            "multiplyOrder",
            MultiplyAmountCommand::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "orderRepository" => InMemoryStandardRepository::createWith([$order]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(MultiplyAmountCommand::create(1, 1, 10))->setReplyChannel(NullableMessageChannel::create())->build());

        $this->expectException(AggregateVersionMismatchException::class);

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(MultiplyAmountCommand::create(1, 1, 10))->setReplyChannel(NullableMessageChannel::create())->build());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
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

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Order::class,
            "multiplyOrder",
            MultiplyAmountCommand::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "orderRepository" => $orderRepository,
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload($commandToRun)->setReplyChannel(NullableMessageChannel::create())->build());
    }


    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_save_method_with_first_version_for_factory_method()
    {
        $commandToRun = CreateOrderCommand::createWith(1, 1, "Poland");

        $orderRepository = $this->createMock(StandardRepository::class);
        $orderRepository->method("canHandle")
            ->with(Order::class)
            ->willReturn(true);
        $orderRepository->method("findBy")
            ->with(Order::class, ["orderId" => 1])
            ->willReturn(null);

        $order = Order::createWith($commandToRun);
        $order->increaseAggregateVersion();
        $order->getRecordedEvents();

        $orderRepository->expects($this->once())
            ->method("save")
            ->with(["orderId" => 1], $order, $this->callback(function(){return true;}), 1);

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Order::class,
            "createWith",
            CreateOrderCommand::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "orderRepository" => $orderRepository,
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload($commandToRun)->setReplyChannel(NullableMessageChannel::create())->build());
    }

    public function test_calling_factory_method_for_event_sourced_aggregate()
    {
        $commandToRun = new StartTicketCommand(1);

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Ticket::class,
            "start",
            StartTicketCommand::class
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");

        $queueChannel = QueueChannel::create();
        $inMemoryEventSourcedRepository = InMemoryEventSourcedRepository::createEmpty();

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => $queueChannel
            ]),
            InMemoryReferenceSearchService::createWith([
                "repository" => $inMemoryEventSourcedRepository,
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload($commandToRun)->setReplyChannel(NullableMessageChannel::create())->build());

        $expectedEvent = new TicketWasStartedEvent(1);
        $this->assertEquals(
            [$expectedEvent],
            $inMemoryEventSourcedRepository->findBy(Ticket::class, ["ticketId" => 1])
        );
        $this->assertEquals(
            $expectedEvent,
            $queueChannel->receive()->getPayload()
        );
    }

    public function test_calling_action_method_for_existing_event_sourced_aggregate()
    {
        $ticketId = 1;
        $commandToRun = new AssignWorkerCommand($ticketId, 100);

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Ticket::class,
            "assignWorker",
            AssignWorkerCommand::class
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");

        $queueChannel = QueueChannel::create();
        $inMemoryEventSourcedRepository = InMemoryEventSourcedRepository::createWithExistingAggregate(["ticketId" => $ticketId], [new TicketWasStartedEvent($ticketId)]);

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => $queueChannel
            ]),
            InMemoryReferenceSearchService::createWith([
                "repository" => $inMemoryEventSourcedRepository,
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload($commandToRun)->setReplyChannel(NullableMessageChannel::create())->build());

        $expectedEvent = new WorkerWasAssignedEvent($ticketId, 100);
        $this->assertEquals(
            [new TicketWasStartedEvent($ticketId), $expectedEvent],
            $inMemoryEventSourcedRepository->findBy(Ticket::class, ["ticketId" => $ticketId])
        );
        $this->assertEquals(
            $expectedEvent,
            $queueChannel->receive()->getPayload()
        );
    }

    public function test_calling_second_action_method_for_existing_event_sourced_aggregate()
    {
        $ticketId = 1;
        $commandToRun = new AssignWorkerCommand($ticketId, 101);

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Ticket::class,
            "assignWorker",
            AssignWorkerCommand::class
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");

        $queueChannel = QueueChannel::create();
        $currentEvents = [new TicketWasStartedEvent($ticketId), new WorkerWasAssignedEvent($ticketId, 100)];
        $inMemoryEventSourcedRepository = InMemoryEventSourcedRepository::createWithExistingAggregate(["ticketId" => $ticketId], $currentEvents);

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => $queueChannel
            ]),
            InMemoryReferenceSearchService::createWith([
                "repository" => $inMemoryEventSourcedRepository,
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $aggregateCommandHandler->handle(MessageBuilder::withPayload($commandToRun)->setReplyChannel(NullableMessageChannel::create())->build());

        $expectedEvent = new WorkerAssignationFailedEvent($ticketId, 101);
        $this->assertEquals(
            array_merge($currentEvents, [$expectedEvent]),
            $inMemoryEventSourcedRepository->findBy(Ticket::class, ["ticketId" => $ticketId])
        );
        $this->assertEquals(
            $expectedEvent,
            $queueChannel->receive()->getPayload()
        );
    }

    public function test_throwing_exception_if_no_factory_method_defined_for_event_sourced_aggregate()
    {
        $this->expectException(InvalidArgumentException::class);

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            NoFactoryMethodAggregateExample::class,
            "doSomething",
            null
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");

        $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryEventSourcedRepository::createEmpty(),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );
    }

    public function test_throwing_exception_if_factory_method_for_event_sourced_aggregate_has_no_parameters()
    {
        $this->expectException(InvalidArgumentException::class);

        AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            FactoryMethodWithWrongParameterCountExample::class,
            "doSomething",
            null
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");
    }

    public function test_throwing_exception_if_factory_method_for_event_sourced_aggregate_is_not_static()
    {
        $this->expectException(InvalidArgumentException::class);

        AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            NonStaticFactoryMethodExample::class,
            "doSomething",
            null
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");
    }

    public function test_throwing_exception_if_incorrect_event_types_returned()
    {
        $ticketId = 1;
        $commandToRun = new CreateIncorrectEventTypeReturnedAggregate();

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            IncorrectEventTypeReturnedExample::class,
            "create",
            CreateIncorrectEventTypeReturnedAggregate::class
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");

        $queueChannel = QueueChannel::create();
        $inMemoryEventSourcedRepository = InMemoryEventSourcedRepository::createWithExistingAggregate(["ticketId" => $ticketId], [new TicketWasStartedEvent($ticketId)]);

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => $queueChannel
            ]),
            InMemoryReferenceSearchService::createWith([
                "repository" => $inMemoryEventSourcedRepository,
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $this->expectException(InvalidArgumentException::class);

        $aggregateCommandHandler->handle(MessageBuilder::withPayload($commandToRun)->setReplyChannel(NullableMessageChannel::create())->build());
    }

    public function test_throwing_exception_if_aggregate_before_saving_has_no_nullable_identifier()
    {
        $ticketId = 1;
        $commandToRun = new CreateNoIdDefinedAggregate();

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            NoIdDefinedAfterCallingFactoryExample::class,
            "create",
            CreateNoIdDefinedAggregate::class
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");

        $queueChannel = QueueChannel::create();
        $inMemoryEventSourcedRepository = InMemoryEventSourcedRepository::createWithExistingAggregate(["ticketId" => $ticketId], [new TicketWasStartedEvent($ticketId)]);

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => $queueChannel
            ]),
            InMemoryReferenceSearchService::createWith([
                "repository" => $inMemoryEventSourcedRepository,
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $this->expectException(NoCorrectIdentifierDefinedException::class);

        $aggregateCommandHandler->handle(MessageBuilder::withPayload($commandToRun)->setReplyChannel(NullableMessageChannel::create())->build());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     * @throws \Throwable
     */
    public function test_throwing_exception_when_trying_to_handle_command_without_aggregate_id()
    {
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, "Poland"));
        $order->increaseAggregateVersion();

        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Order::class,
            "finish",
            CommandWithoutAggregateIdentifier::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                LazyEventBus::CHANNEL_NAME => QueueChannel::create()
            ]),
            InMemoryReferenceSearchService::createWith([
                "orderRepository" => InMemoryStandardRepository::createWith([$order]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $this->expectException(AggregateNotFoundException::class);

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(CommandWithoutAggregateIdentifier::create(null))->build());
    }

    /**
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_throwing_exception_if_no_aggregate_identifier_definition_found()
    {
        $this->expectException(InvalidArgumentException::class);

        AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Article::class,
            "changeContent",
            RepublishArticleCommand::class
        );
    }

    /**
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_not_throwing_exception_if_no_aggregate_identifier_definition_found_for_factory_method()
    {
        AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Article::class,
            "createWithoutContent",
            PublishArticleWithTitleOnlyCommand::class
        );

        $this->assertTrue(true, "Created without identifiers");
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \ReflectionException
     */
    public function test_throwing_exception_if_no_identifiers_defined_in_aggregate()
    {
        $this->expectException(InvalidArgumentException::class);

        AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            ReplyViaHeadersMessageHandler::class,
            "handle",
            PublishArticleCommand::class
        );
    }
}