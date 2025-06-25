<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingGatewayModule;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\Config\MessageBusChannel;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\CreateMerchant;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\Merchant;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\MerchantConversion;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\MerchantSubscriber;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\User;
use Test\Ecotone\Modelling\Fixture\EmailNotifier\ANotification;
use Test\Ecotone\Modelling\Fixture\EmailNotifier\EmailNotifier;
use Test\Ecotone\Modelling\Fixture\EventSourcedSaga\OrderDispatch;
use Test\Ecotone\Modelling\Fixture\EventSourcedSaga\OrderWasCreated;
use Test\Ecotone\Modelling\Fixture\EventSourcedSaga\PaymentWasDoneEvent;
use Test\Ecotone\Modelling\Fixture\HandlerWithAbstractClass\TestAbstractHandler;
use Test\Ecotone\Modelling\Fixture\HandlerWithAbstractClass\TestCommand;
use Test\Ecotone\Modelling\Fixture\HandlerWithAbstractClass\TestHandler;
use Test\Ecotone\Modelling\Fixture\NamedEvent\GuestViewer;
use Test\Ecotone\Modelling\Fixture\NamedEvent\GuestWasAddedToBook;
use Test\Ecotone\Modelling\Fixture\NamedEventAsyncSubscriber\GuestNotifier;
use Test\Ecotone\Modelling\Fixture\NoEventsReturnedFromFactoryMethod\Aggregate;
use Test\Ecotone\Modelling\Fixture\Outbox\OutboxWithMultipleChannels;
use Test\Ecotone\Modelling\Fixture\PriorityEventHandler\AggregateSynchronousPriorityWithHigherPriorityHandler;
use Test\Ecotone\Modelling\Fixture\PriorityEventHandler\AggregateSynchronousPriorityWithLowerPriorityHandler;
use Test\Ecotone\Modelling\Fixture\PriorityEventHandler\OrderWasPlaced;
use Test\Ecotone\Modelling\Fixture\PriorityEventHandler\SynchronousPriorityHandler;
use Test\Ecotone\Modelling\Fixture\PriorityEventHandler\SynchronousPriorityHandlerWithInheritance;

/**
 * licence Apache-2.0
 * @internal
 */
#[CoversClass(CommandBus::class)]
#[CoversClass(EventBus::class)]
#[CoversClass(QueryBus::class)]
#[CoversClass(MessagingGatewayModule::class)]
final class MessageBusTest extends TestCase
{
    public function test_command_event_command_flow()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [Merchant::class, User::class, MerchantSubscriber::class],
            [
                new MerchantSubscriber(),
            ],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
            allowGatewaysToBeRegisteredInContainer: true
        );

        $merchantId = '123';
        $this->assertTrue(
            $ecotoneTestSupport
                ->sendCommand(new CreateMerchant($merchantId))
                ->sendQueryWithRouting('user.get', metadata: ['aggregate.id' => $merchantId])
        );
    }

    public function test_synchronous_event_handlers_should_be_handled_in_priority()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [SynchronousPriorityHandler::class],
            [
                new SynchronousPriorityHandler(),
            ]
        );

        $this->assertSame(
            ['higherPriorityHandler', 'middlePriorityHandler', 'lowerPriorityHandler'],
            $ecotoneTestSupport
                ->publishEvent(new OrderWasPlaced(1))
                ->sendQueryWithRouting('getTriggers')
        );
    }

    public function test_synchronous_event_handlers_should_be_handled_in_priority_with_inheritance()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [SynchronousPriorityHandlerWithInheritance::class],
            [
                new SynchronousPriorityHandlerWithInheritance(),
            ]
        );

        $this->assertSame(
            ['higherPriorityHandler', 'middlePriorityHandler', 'lowerPriorityHandlerWithObjectRouting'],
            $ecotoneTestSupport
                ->publishEvent(new OrderWasPlaced(1))
                ->sendQueryWithRouting('getTriggers')
        );
    }

    public function test_aggregate_and_service_synchronous_event_handlers_should_be_handled_in_priority()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [AggregateSynchronousPriorityWithLowerPriorityHandler::class, SynchronousPriorityHandler::class],
            [
                new SynchronousPriorityHandler(),
            ]
        );

        $this->assertSame(
            [
                'higherPriorityHandler',
                'middlePriorityHandler',
                'aggregateLowerPriorityHandler',
                'lowerPriorityHandler',
            ],
            $ecotoneTestSupport
                ->sendCommandWithRoutingKey('setup', 1)
                ->sendQueryWithRouting('getTriggers')
        );
    }

    public function test_when_aggregate_and_service_with_same_priority_aggregate_should_go_first()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [AggregateSynchronousPriorityWithHigherPriorityHandler::class, SynchronousPriorityHandler::class],
            [
                new SynchronousPriorityHandler(),
            ]
        );

        $this->assertSame(
            [
                'aggregateHigherPriorityHandler',
                'higherPriorityHandler',
                'middlePriorityHandler',
                'lowerPriorityHandler',
            ],
            $ecotoneTestSupport
                ->sendCommandWithRoutingKey('setup', 1)
                ->sendQueryWithRouting('getTriggers')
        );
    }

    public function test_calling_command_handler_with_abstract_class()
    {
        $ecotoneLite = EcotoneLite::bootstrapForTesting(
            [TestHandler::class, TestAbstractHandler::class],
            [
                new TestHandler(),
            ],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        $this->assertEquals(
            1,
            $ecotoneLite->getCommandBus()->send(new TestCommand(1))
        );
    }

    public function test_calling_asynchronous_command_handler_with_pass_through_message_channels()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OutboxWithMultipleChannels::class],
            [
                new OutboxWithMultipleChannels(),
            ],
            ServiceConfiguration::createWithAsynchronicityOnly()
        );

        $ecotoneLite->sendCommandWithRoutingKey('outboxWithMultipleChannels', 1);
        $this->assertEquals(
            0,
            $ecotoneLite->sendQueryWithRouting('getResult')
        );

        $ecotoneLite->run('outbox');
        $this->assertEquals(
            0,
            $ecotoneLite->sendQueryWithRouting('getResult')
        );

        $ecotoneLite->run('rabbitMQ');
        $this->assertEquals(
            1,
            $ecotoneLite->sendQueryWithRouting('getResult')
        );
    }

    public function test_calling_asynchronous_command_handler_with_combined_message_channel()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OutboxWithMultipleChannels::class],
            [
                new OutboxWithMultipleChannels(),
            ],
            ServiceConfiguration::createWithAsynchronicityOnly()
        );

        $ecotoneLite->sendCommandWithRoutingKey('outboxWithCombinedChannels', 1);
        $this->assertEquals(
            0,
            $ecotoneLite->sendQueryWithRouting('getResult')
        );

        $ecotoneLite->run('outbox');
        $this->assertEquals(
            0,
            $ecotoneLite->sendQueryWithRouting('getResult')
        );

        $ecotoneLite->run('rabbitMQ');
        $this->assertEquals(
            1,
            $ecotoneLite->sendQueryWithRouting('getResult')
        );
    }

    public function test_event_flow_with_event_sourcing_aggregate()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrderDispatch::class],
            [],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        $this->assertEquals(
            'new',
            $ecotoneLite->publishEvent(new OrderWasCreated('1'))
                ->sendQueryWithRouting('order_dispatch.getStatus', metadata: ['aggregate.id' => '1'])
        );
        $this->assertEquals(
            'closed',
            $ecotoneLite->publishEvent(new PaymentWasDoneEvent('1'))
                ->sendQueryWithRouting('order_dispatch.getStatus', metadata: ['aggregate.id' => '1'])
        );
    }

    public function test_factory_method_of_event_sourced_aggregate_can_return_no_events(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [Aggregate::class],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        self::assertEquals(
            [],
            $ecotoneLite
                ->sendCommandWithRoutingKey('aggregate.create')
                ->getRecordedEvents()
        );
    }

    public function test_named_event_are_aliased_without_being_part_of_resolved_classes(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [
                GuestViewer::class,
                // GuestWasAddedToBook::class // Not necessary to resolve named event for aliasing
            ],
            containerOrAvailableServices: [new GuestViewer()],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        self::assertEquals(
            ['John Doe'],
            $ecotoneLite
                ->publishEvent(new GuestWasAddedToBook('book-1', 'John Doe'))
                ->sendQueryWithRouting(GuestViewer::BOOK_GET_GUESTS, 'book-1'),
            'Named event should be aliased to GuestWasAddedToBook without being part of resolved classes'
        );
    }

    public function test_it_does_use_endpoint_ids_as_routing_slips_to_ensure_it_kept_static(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [
                GuestNotifier::class,
            ],
            containerOrAvailableServices: [new GuestNotifier()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                SimpleMessageChannelBuilder::createQueueChannel('background'),
            ]
        );

        $ecotoneLite
            ->publishEvent(new GuestWasAddedToBook('book-1', 'John Doe'));

        $channelAsync = $ecotoneLite->getMessageChannel('async');
        $channelBackground = $ecotoneLite->getMessageChannel('background');

        $message = $channelAsync->receive();
        self::assertNotNull($message);
        self::assertEquals('guestViewer.notify1.target.execute', $message->getHeaders()->get(MessageHeaders::ROUTING_SLIP));

        $message = $channelAsync->receive();
        self::assertNotNull($message);
        self::assertEquals('guestViewer.notify2.target.execute', $message->getHeaders()->get(MessageHeaders::ROUTING_SLIP));

        $message = $channelBackground->receive();
        self::assertNotNull($message);
        self::assertEquals('guestViewer.notify3.target.execute', $message->getHeaders()->get(MessageHeaders::ROUTING_SLIP));
    }

    public function test_sending_to_command_channel_directly_with_type_id_and_serialized_payload(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [Merchant::class, MerchantConversion::class],
            [new MerchantConversion()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
        );

        $merchantId = '123';

        $this->assertSame(
            $merchantId, // factory method returns aggregate id
            $ecotoneTestSupport
                ->sendDirectToChannel(
                    MessageBusChannel::COMMAND_CHANNEL_NAME_BY_OBJECT,
                    ['merchantId' => $merchantId],
                    metadata: [
                        MessageBusChannel::COMMAND_CHANNEL_NAME_BY_NAME => 'create.merchant',
                        MessageHeaders::TYPE_ID => CreateMerchant::class,
                        MessageHeaders::CONTENT_TYPE => MediaType::createApplicationXPHPArray()->toString(),
                    ]
                )
        );
    }

    public function test_it_can_route_by_interface(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [EmailNotifier::class, ],
            containerOrAvailableServices: [$emailNotifier = new EmailNotifier()],
        );

        $ecotoneLite->publishEvent($notification = new ANotification('john doe'));
        $this->assertCount(1, $emailNotifier->getEmails());
        $this->assertEquals(
            $notification,
            $emailNotifier->getEmails()[0]
        );
    }
}
