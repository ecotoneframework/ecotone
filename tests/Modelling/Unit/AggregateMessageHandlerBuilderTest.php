<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessageHandlerBuilder;
use Ecotone\Modelling\AggregateNotFoundException;
use Ecotone\Modelling\AggregateVersionMismatchException;
use Ecotone\Modelling\LazyEventBus\LazyEventBus;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateWithoutMessageClassesExample;
use Test\Ecotone\Modelling\Fixture\Blog\Article;
use Test\Ecotone\Modelling\Fixture\Blog\ChangeArticleContentCommand;
use Test\Ecotone\Modelling\Fixture\Blog\CloseArticleCommand;
use Test\Ecotone\Modelling\Fixture\Blog\InMemoryArticleRepository;
use Test\Ecotone\Modelling\Fixture\Blog\InMemoryArticleRepositoryFactory;
use Test\Ecotone\Modelling\Fixture\Blog\PublishArticleCommand;
use Test\Ecotone\Modelling\Fixture\Blog\PublishArticleWithTitleOnlyCommand;
use Test\Ecotone\Modelling\Fixture\Blog\RepublishArticleCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\ChangeShippingAddressCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\CommandWithoutAggregateIdentifier;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\GetOrderAmountQuery;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryAggregateRepository;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryOrderAggregateRepositoryConstructor;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryOrderRepositoryFactory;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\MultiplyAmountCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\Order;
use Test\Ecotone\Modelling\Fixture\Handler\ReplyViaHeadersMessageHandler;
use Test\Ecotone\Modelling\Fixture\TestingEventBus;
use Test\Ecotone\Modelling\Fixture\TestingLazyEventBus;

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
                "orderRepository" => InMemoryAggregateRepository::createWith([$order])
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
                "repository" => InMemoryAggregateRepository::createWith([$aggregate])
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
                "repository" => InMemoryAggregateRepository::createWith([$aggregate])
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
                "repository" => InMemoryAggregateRepository::createWith([$aggregate]),
                \stdClass::class => new \stdClass()
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
                "repository" => InMemoryAggregateRepository::createWith([$aggregate])
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
                "orderRepository" => InMemoryAggregateRepository::createWith([$order])
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
                "repository" => InMemoryAggregateRepository::createWith([$aggregate])
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
                "repository" => InMemoryAggregateRepository::createWith([$aggregate])
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
                "orderRepository" => InMemoryAggregateRepository::createWith([$order])
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
                "orderRepository" => InMemoryAggregateRepository::createWith([$order])
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
                "orderRepository" => InMemoryAggregateRepository::createEmpty()
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
                "orderRepository" => InMemoryAggregateRepository::createWith([
                    $order
                ])
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
                "orderRepository" => InMemoryAggregateRepository::createEmpty()
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
                "articleRepository" => InMemoryArticleRepository::createEmpty()
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
                "repository" => InMemoryAggregateRepository::createEmpty()
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
                "repository" => InMemoryAggregateRepository::createEmpty()
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
                "articleRepository" => InMemoryArticleRepository::createWith([
                    Article::createWith(PublishArticleCommand::createWith("johny", "Tolkien", "some bla bla"))
                ])
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
                "articleRepository" => InMemoryArticleRepository::createWith([
                    Article::createWith(PublishArticleCommand::createWith("johny", "Tolkien", "some bla bla"))
                ])
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
    public function test_calling_aggregate_with_version_locking()
    {
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, "Poland"));
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
                "orderRepository" => InMemoryAggregateRepository::createWith([$order])
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
     * @throws \Throwable
     */
    public function test_throwing_exception_when_trying_to_handle_command_without_aggregate_id()
    {
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, "Poland"));
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
                "orderRepository" => InMemoryAggregateRepository::createWith([$order])
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

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function test_throwing_exception_if_version_locking_defined_and_no_version_provided()
    {
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, "Poland"));
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
                "orderRepository" => InMemoryAggregateRepository::createWith([$order])
            ])
        );

        $this->expectException(InvalidArgumentException::class);

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(MultiplyAmountCommand::create(1, null, 10))->build());
    }
}