<?php

namespace Test\SimplyCodedSoftware\DomainModel\Unit;

use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\DomainModel\AggregateMessageHandlerBuilder;
use SimplyCodedSoftware\DomainModel\AggregateNotFoundException;
use SimplyCodedSoftware\DomainModel\AggregateVersionMismatchException;
use SimplyCodedSoftware\DomainModel\EventBus;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ReferenceBuilder;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\NullableMessageChannel;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Blog\Article;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Blog\ChangeArticleContentCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Blog\CloseArticleCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Blog\InMemoryArticleRepository;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Blog\InMemoryArticleRepositoryFactory;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Blog\PublishArticleCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Blog\PublishArticleWithTitleOnlyCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Blog\RepublishArticleCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\ChangeShippingAddressCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\CommandWithoutAggregateIdentifier;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\GetOrderAmountQuery;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\InMemoryAggregateRepository;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\InMemoryOrderAggregateRepositoryConstructor;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\InMemoryOrderRepositoryFactory;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\MultiplyAmountCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\Order;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\OrderNotificator;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Handler\ReplyViaHeadersMessageHandler;
use Test\SimplyCodedSoftware\DomainModel\Fixture\LazyEventBus;

/**
 * Class ServiceCallToAggregateAdapterTest
 * @package Test\SimplyCodedSoftware\DomainModel
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateMessageHandlerBuilderTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_existing_aggregate_method_with_only_command_as_parameter()
    {
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, "Poland"), new LazyEventBus());
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Order::class,
            "changeShippingAddress",
            ChangeShippingAddressCommand::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"]);

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
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
    public function test_configuring_command_handler()
    {
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Order::class,
            "changeShippingAddress",
            ChangeShippingAddressCommand::class
        )->withInputChannelName(ChangeShippingAddressCommand::class);

        $this->assertEquals(ChangeShippingAddressCommand::class, $aggregateCallingCommandHandler->getInputMessageChannelName());
        $this->assertEquals([], $aggregateCallingCommandHandler->getRequiredReferenceNames());

        $aggregateCallingCommandHandler->registerRequiredReference("some-ref");
        $this->assertEquals(["some-ref"], $aggregateCallingCommandHandler->getRequiredReferenceNames());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_calling_aggregate_for_query_handler_with_return_value()
    {
        $orderAmount = 5;
        $order = Order::createWith(CreateOrderCommand::createWith(1, $orderAmount, "Poland"), new LazyEventBus());
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith(
            Order::class,
            "getAmountWithQuery",
            GetOrderAmountQuery::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"]);

        $aggregateQueryHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
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
    public function test_calling_aggregate_for_query_handler_with_output_channel()
    {
        $outputChannelName = "output";
        $orderAmount = 5;
        $order = Order::createWith(CreateOrderCommand::createWith(1, $orderAmount, "Poland"), new LazyEventBus());
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
                    $outputChannelName => $outputChannel
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
        $order = Order::createWith(CreateOrderCommand::createWith(1, $orderAmount, "Poland"), new LazyEventBus());
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith(
            Order::class,
            "getAmount",
            GetOrderAmountQuery::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateQueryHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
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
            ->withMethodParameterConverters([
                PayloadBuilder::create("command"),
                ReferenceBuilder::create("eventBus", EventBus::class)
            ])
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                EventBus::class => new LazyEventBus(),
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
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, "Poland"), new LazyEventBus());
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Order::class,
            "createWith",
            CreateOrderCommand::class
        )
            ->withMethodParameterConverters([
                PayloadBuilder::create("command"),
                ReferenceBuilder::create("eventBus", EventBus::class)
            ])
            ->withRedirectToOnAlreadyExists("increaseAmount", [
                PayloadBuilder::create("command"),
                ReferenceBuilder::create("eventBus", EventBus::class)
            ])
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                EventBus::class => new LazyEventBus(),
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
                PayloadBuilder::create("command"),
                ReferenceBuilder::create("eventBus", EventBus::class)
            ])
            ->withRedirectToOnAlreadyExists("increaseAmount", [
                PayloadBuilder::create("command"),
                ReferenceBuilder::create("eventBus", EventBus::class)
            ])
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                EventBus::class => new LazyEventBus(),
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
            InMemoryChannelResolver::createEmpty(),
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
            InMemoryChannelResolver::createEmpty(),
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
            InMemoryChannelResolver::createEmpty(),
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
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, "Poland"), new LazyEventBus());
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Order::class,
            "multiplyOrder",
            MultiplyAmountCommand::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
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
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, "Poland"), new LazyEventBus());
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Order::class,
            "finish",
            CommandWithoutAggregateIdentifier::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
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
        $order = Order::createWith(CreateOrderCommand::createWith(1, 1, "Poland"), new LazyEventBus());
        $aggregateCallingCommandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith(
            Order::class,
            "multiplyOrder",
            MultiplyAmountCommand::class
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                "orderRepository" => InMemoryAggregateRepository::createWith([$order])
            ])
        );

        $this->expectException(InvalidArgumentException::class);

        $aggregateCommandHandler->handle(MessageBuilder::withPayload(MultiplyAmountCommand::create(1, null, 10))->build());
    }
}