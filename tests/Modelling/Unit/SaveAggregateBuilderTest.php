<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Config\BusModule;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\InMemoryEventSourcedRepository;
use Ecotone\Modelling\NoCorrectIdentifierDefinedException;
use Ecotone\Modelling\SaveAggregateServiceBuilder;
use Ecotone\Modelling\StandardRepository;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\MultiplyAmountCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\Order;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\OrderWithManualVersioning;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory\NoIdDefinedAfterCallingFactoryExample;

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
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    BusModule::EVENT_CHANNEL_NAME_BY_OBJECT => QueueChannel::create()
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
            "multiplyOrder"
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    BusModule::EVENT_CHANNEL_NAME_BY_OBJECT => QueueChannel::create()
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
            "multiplyOrder"
        )
            ->withAggregateRepositoryFactories(["orderRepository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    BusModule::EVENT_CHANNEL_NAME_BY_OBJECT => QueueChannel::create()
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
            "create"
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createFromAssociativeArray(
                [
                    BusModule::EVENT_CHANNEL_NAME_BY_OBJECT => QueueChannel::create()
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
}