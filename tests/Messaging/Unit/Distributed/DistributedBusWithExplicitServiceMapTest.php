<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Distributed;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Lite\Test\FlowTestSupport;
use Ecotone\Messaging\Channel\DynamicChannel\DynamicMessageChannelBuilder;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Consumer\ConsumerPositionTracker;
use Ecotone\Messaging\Consumer\InMemory\InMemoryConsumerPositionTracker;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Api\Distribution\DistributedBusHeader;
use Ecotone\Modelling\Api\Distribution\DistributedServiceMap;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\DistributedBus;
use Ecotone\Modelling\MessageHandling\Distribution\UnknownDistributedDestination;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\CommandConverter\RegisterTicketConverter;
use Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Publisher\UserService;
use Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Receiver\RegisterTicket;
use Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Receiver\TicketServiceReceiver;
use Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverOrder\OrderServiceReceiver;
use Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicketWithConversion\UserChangedAddress;
use Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicketWithConversion\UserChangedAddressConverter;
use Test\Ecotone\Messaging\Fixture\Distributed\DistributedSendInterceptor\DistributedSendInterceptor;
use Test\Ecotone\Messaging\Fixture\Distributed\TestServiceName;

/**
 * licence Enterprise
 * @internal
 */
final class DistributedBusWithExplicitServiceMapTest extends TestCase
{
    public const SERVICE_NAME = 'ticket_service';

    public function test_distributing_command_to_another_service(): void
    {
        $sharedQueueChannel = SimpleMessageChannelBuilder::createQueueChannel($channelName = 'distributed_ticket_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Publisher'],
            [],
            $sharedQueueChannel,
            DistributedServiceMap::initialize()
                ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $channelName)
        );
        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Receiver'], [new TicketServiceReceiver()], $sharedQueueChannel);

        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));

        $userService->getDistributedBus()->sendCommand(
            TestServiceName::TICKET_SERVICE,
            TicketServiceReceiver::CREATE_TICKET_ENDPOINT,
            'User changed billing address',
            metadata: [
                'token' => '123',
            ]
        );
        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));

        $ticketService->run($sharedQueueChannel->getMessageChannelName(), ExecutionPollingMetadata::createWithTestingSetup());
        self::assertEquals(1, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));
        self::assertEquals(
            'User changed billing address',
            $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS)[0]->getPayload()
        );
        self::assertEquals(
            '123',
            $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS)[0]->getHeaders()->get('token')
        );
    }

    public function test_distributing_command_to_another_service_with_conversion(): void
    {
        $sharedQueueChannel = SimpleMessageChannelBuilder::createQueueChannel($channelName = 'distributed_ticket_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Publisher', 'Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\CommandConverter'],
            [new RegisterTicketConverter()],
            $sharedQueueChannel,
            DistributedServiceMap::initialize()
                ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $channelName)
        );
        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Receiver', 'Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\CommandConverter'], [new TicketServiceReceiver(), new RegisterTicketConverter()], $sharedQueueChannel);

        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));

        $userService->getDistributedBus()->convertAndSendCommand(
            TestServiceName::TICKET_SERVICE,
            TicketServiceReceiver::CREATE_TICKET_ENDPOINT_WITH_CONVERSION,
            new RegisterTicket('someId'),
        );
        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));

        $ticketService->run($sharedQueueChannel->getMessageChannelName(), ExecutionPollingMetadata::createWithTestingSetup());
        self::assertEquals(1, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));
        self::assertEquals(
            new RegisterTicket('someId'),
            $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS)[0]
        );
    }

    public function test_distributing_command_to_another_service_via_command_handler(): void
    {
        $sharedQueueChannel = SimpleMessageChannelBuilder::createQueueChannel($channelName = 'distributed_ticket_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Publisher'],
            [new UserService()],
            $sharedQueueChannel,
            DistributedServiceMap::initialize()
                ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $channelName)
        );
        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Receiver'], [new TicketServiceReceiver()], $sharedQueueChannel);

        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));

        $userService->sendCommandWithRoutingKey(UserService::CHANGE_BILLING_DETAILS, 'change details');
        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));

        $ticketService->run($sharedQueueChannel->getMessageChannelName(), ExecutionPollingMetadata::createWithTestingSetup());
        self::assertEquals(1, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));
    }

    public function test_failing_on_distribution_to_not_mapped_service(): void
    {
        $sharedQueueChannel = SimpleMessageChannelBuilder::createQueueChannel('distributed_ticket_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Publisher'],
            [],
            $sharedQueueChannel,
            DistributedServiceMap::initialize()
        );

        $this->expectException(UnknownDistributedDestination::class);

        $userService->getDistributedBus()->sendCommand(
            TestServiceName::TICKET_SERVICE,
            TicketServiceReceiver::CREATE_TICKET_ENDPOINT,
            'User changed billing address',
            metadata: [
                'token' => '123',
            ]
        );

        $userService->sendCommandWithRoutingKey(UserService::CHANGE_BILLING_DETAILS, 'change details');
    }

    public function test_failing_on_distribution_to_not_existing_message_channel_service(): void
    {
        $this->expectException(ConfigurationException::class);

        $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Publisher'],
            [new UserService()],
            extensionObjects: DistributedServiceMap::initialize()
                                            ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: 'not_existing_channel')
        );
    }

    public function test_distributing_event_to_another_service(): void
    {
        $sharedQueueChannel = SimpleMessageChannelBuilder::createQueueChannel($channelName = 'distributed_ticket_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            [],
            [],
            $sharedQueueChannel,
            DistributedServiceMap::initialize()
                ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $channelName)
                ->withEventMapping(channelName: $channelName, subscriptionKeys: ['*'])
        );
        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket'], [new \Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket\TicketServiceReceiver()], $sharedQueueChannel);

        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));

        $userService->getDistributedBus()->publishEvent(
            'userService.billing.DetailsWereChanged',
            'User changed billing address',
            metadata: ['token' => '123']
        );
        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));

        $ticketService->run($sharedQueueChannel->getMessageChannelName(), ExecutionPollingMetadata::createWithTestingSetup());
        self::assertEquals(1, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));
        self::assertEquals(
            'User changed billing address',
            $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS)[0]->getPayload()
        );
        self::assertEquals(
            '123',
            $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS)[0]->getHeaders()->get('token')
        );
    }

    public function test_distributing_event_to_another_service_with_conversion(): void
    {
        $sharedQueueChannel = SimpleMessageChannelBuilder::createQueueChannel($channelName = 'distributed_ticket_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicketWithConversion'],
            [new UserChangedAddressConverter()],
            $sharedQueueChannel,
            DistributedServiceMap::initialize()
                ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $channelName)
                ->withEventMapping(channelName: $channelName, subscriptionKeys: ['*'])
        );
        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicketWithConversion'], [new \Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicketWithConversion\TicketServiceReceiver(), new UserChangedAddressConverter()], $sharedQueueChannel);

        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));

        $userService->getDistributedBus()->convertAndPublishEvent(
            'userService.billing.DetailsWereChanged',
            new UserChangedAddress('123'),
        );
        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));

        $ticketService->run($sharedQueueChannel->getMessageChannelName(), ExecutionPollingMetadata::createWithTestingSetup());
        self::assertEquals(1, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));
        self::assertEquals(
            new UserChangedAddress('123'),
            $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS)[0]
        );
    }

    public function test_distributing_event_to_all_services_within_map(): void
    {
        $distributedTicketQueue = SimpleMessageChannelBuilder::createQueueChannel($ticketChannelName = 'distributed_ticket_channel');
        $distributedOrderQueue = SimpleMessageChannelBuilder::createQueueChannel($orderChannelName = 'distributed_order_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            [],
            [],
            [$distributedTicketQueue, $distributedOrderQueue],
            DistributedServiceMap::initialize()
                ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $ticketChannelName)
                ->withCommandMapping(targetServiceName: TestServiceName::ORDER_SERVICE, channelName: $orderChannelName)
                ->withEventMapping(channelName: $ticketChannelName, subscriptionKeys: ['*'])
                ->withEventMapping(channelName: $orderChannelName, subscriptionKeys: ['*'])
        );
        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket'], [new \Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket\TicketServiceReceiver()], $distributedTicketQueue);
        $orderService = $this->bootstrapEcotone(TestServiceName::ORDER_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverOrder'], [new OrderServiceReceiver()], $distributedOrderQueue);

        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));
        self::assertEquals(0, $orderService->sendQueryWithRouting(OrderServiceReceiver::GET_ORDERS_COUNT));

        $userService->getDistributedBus()->publishEvent(
            'userService.billing.DetailsWereChanged',
            'User changed billing address',
            metadata: ['token' => '123']
        );
        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));
        self::assertEquals(0, $orderService->sendQueryWithRouting(OrderServiceReceiver::GET_ORDERS_COUNT));

        $ticketService->run($distributedTicketQueue->getMessageChannelName(), ExecutionPollingMetadata::createWithTestingSetup());
        self::assertEquals(1, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));
        self::assertEquals(0, $orderService->sendQueryWithRouting(OrderServiceReceiver::GET_ORDERS_COUNT));

        $orderService->run($distributedOrderQueue->getMessageChannelName(), ExecutionPollingMetadata::createWithTestingSetup());
        self::assertEquals(1, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));
        self::assertEquals(1, $orderService->sendQueryWithRouting(OrderServiceReceiver::GET_ORDERS_COUNT));
    }

    public function test_it_does_not_publish_event_to_publishing_service_when_excluded(): void
    {
        $distributedTicketQueue = SimpleMessageChannelBuilder::createQueueChannel($ticketChannelName = 'distributed_ticket_channel');
        $distributedUserQueue = SimpleMessageChannelBuilder::createQueueChannel($userChannelName = 'distributed_order_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            [],
            [],
            [$distributedTicketQueue, $distributedUserQueue],
            DistributedServiceMap::initialize()
                ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $ticketChannelName)
                ->withCommandMapping(targetServiceName: TestServiceName::USER_SERVICE, channelName: $userChannelName)
                ->withEventMapping(channelName: $ticketChannelName, subscriptionKeys: ['*'])
                ->withEventMapping(channelName: $userChannelName, subscriptionKeys: ['*'], excludePublishingServices: [TestServiceName::USER_SERVICE])
        );
        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket'], [new \Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket\TicketServiceReceiver()], $distributedTicketQueue);

        $userService->getDistributedBus()->publishEvent(
            'userService.billing.DetailsWereChanged',
            'User changed billing address',
            metadata: ['token' => '123']
        );

        self::assertNotNull($ticketService->getMessageChannel($ticketChannelName)->receive());
        self::assertNull($userService->getMessageChannel($userChannelName)->receive());
    }

    public function test_publishing_events_with_name_filtering(): void
    {
        $distributedTicketQueue = SimpleMessageChannelBuilder::createQueueChannel($ticketChannelName = 'distributed_ticket_channel');
        $distributedOrderQueue = SimpleMessageChannelBuilder::createQueueChannel($orderChannelName = 'distributed_order_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            [],
            [],
            [$distributedTicketQueue, $distributedOrderQueue],
            DistributedServiceMap::initialize()
                ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $ticketChannelName)
                ->withCommandMapping(targetServiceName: TestServiceName::ORDER_SERVICE, channelName: $orderChannelName)
                ->withEventMapping(channelName: $ticketChannelName, subscriptionKeys: ['userService.*'])
                ->withEventMapping(channelName: $orderChannelName, subscriptionKeys: ['ticketService.*'])
        );
        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket'], [new \Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket\TicketServiceReceiver()], $distributedTicketQueue);
        $orderService = $this->bootstrapEcotone(TestServiceName::ORDER_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverOrder'], [new OrderServiceReceiver()], $distributedOrderQueue);

        $userService->getDistributedBus()->publishEvent(
            'userService.billing.DetailsWereChanged',
            'User changed billing address',
            metadata: ['token' => '123']
        );

        self::assertNotNull($ticketService->getMessageChannel($ticketChannelName)->receive());
        self::assertNull($orderService->getMessageChannel($orderChannelName)->receive());
    }

    public function test_if_two_keys_match_event_is_published_once(): void
    {
        $distributedTicketQueue = SimpleMessageChannelBuilder::createQueueChannel($ticketChannelName = 'distributed_ticket_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            [],
            [],
            [$distributedTicketQueue],
            DistributedServiceMap::initialize()
                ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $ticketChannelName)
                ->withEventMapping(channelName: $ticketChannelName, subscriptionKeys: ['userService.*', '*'])
        );
        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket'], [new \Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket\TicketServiceReceiver()], $distributedTicketQueue);

        $userService->getDistributedBus()->publishEvent(
            'userService.billing.DetailsWereChanged',
            'User changed billing address',
            metadata: ['token' => '123']
        );

        self::assertNotNull($ticketService->getMessageChannel($ticketChannelName)->receive());
        self::assertNull($ticketService->getMessageChannel($ticketChannelName)->receive());
    }

    public function test_not_receiving_when_no_event_mapping(): void
    {
        $distributedTicketQueue = SimpleMessageChannelBuilder::createQueueChannel($ticketChannelName = 'distributed_ticket_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            [],
            [],
            [$distributedTicketQueue],
            DistributedServiceMap::initialize()
                ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $ticketChannelName)
            // No event mapping - so no events should be received
        );
        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket'], [new \Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket\TicketServiceReceiver()], $distributedTicketQueue);

        $userService->getDistributedBus()->publishEvent(
            'userService.billing.DetailsWereChanged',
            'User changed billing address',
            metadata: ['token' => '123']
        );

        self::assertNull($ticketService->getMessageChannel($ticketChannelName)->receive());
    }

    /**
     * Example how it would work with Outbox
     */
    public function test_sending_to_distributed_bus_with_middleware_channel(): void
    {
        $outboxChannel = SimpleMessageChannelBuilder::createQueueChannel('outbox');
        $sharedQueueChannel = SimpleMessageChannelBuilder::createQueueChannel($channelName = 'distributed_ticket_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Publisher'],
            [],
            [$sharedQueueChannel, $outboxChannel],
            DistributedServiceMap::initialize()
                ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $channelName)
                ->withAsynchronousChannel('outbox')
        );
        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Receiver'], [new TicketServiceReceiver()], $sharedQueueChannel);

        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));

        $userService->getDistributedBus()->sendCommand(
            TestServiceName::TICKET_SERVICE,
            TicketServiceReceiver::CREATE_TICKET_ENDPOINT,
            'User changed billing address',
            metadata: [
                'token' => '123',
            ]
        );

        /** It should not be in distributed queue yet */
        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));
        $ticketService->run($sharedQueueChannel->getMessageChannelName(), ExecutionPollingMetadata::createWithTestingSetup());
        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));

        /** Running outbox in Distributing Service */
        $userService->run($outboxChannel->getMessageChannelName(), ExecutionPollingMetadata::createWithTestingSetup());
        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));

        /** It should be distributed */
        $ticketService->run($sharedQueueChannel->getMessageChannelName(), ExecutionPollingMetadata::createWithTestingSetup());
        self::assertEquals(1, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));
        self::assertEquals(
            'User changed billing address',
            $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS)[0]->getPayload()
        );
        self::assertEquals(
            '123',
            $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS)[0]->getHeaders()->get('token')
        );
    }

    public function test_registering_more_than_one_service_map(): void
    {
        $distributedTicketQueue = SimpleMessageChannelBuilder::createQueueChannel($ticketChannelName = 'distributed_ticket_channel');
        $distributedOrderQueue = SimpleMessageChannelBuilder::createQueueChannel($orderChannelName = 'distributed_order_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Publisher'],
            [],
            [$distributedTicketQueue, $distributedOrderQueue],
            [
                DistributedServiceMap::initialize(referenceName: $internalDistributedBus = DistributedBus::class . '-internal')
                   ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $ticketChannelName),
                DistributedServiceMap::initialize(referenceName: $externalDistributedBus = DistributedBus::class . '-external')
                    ->withCommandMapping(targetServiceName: TestServiceName::ORDER_SERVICE, channelName: $orderChannelName)
                    ->withEventMapping(channelName: $orderChannelName, subscriptionKeys: ['*']),
            ],
        );
        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Receiver'], [new TicketServiceReceiver()], $distributedTicketQueue);
        $orderService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverOrder'], [new OrderServiceReceiver()], $distributedOrderQueue);

        $userService->getDistributedBus($internalDistributedBus)->sendCommand(
            TestServiceName::TICKET_SERVICE,
            TicketServiceReceiver::CREATE_TICKET_ENDPOINT,
            'User changed billing address',
            metadata: [
                'token' => '123',
            ]
        );

        $this->assertNotNull($ticketService->getMessageChannel($ticketChannelName)->receive());
        ;
        $this->assertNull($orderService->getMessageChannel($orderChannelName)->receive());
        ;

        $userService->getDistributedBus($externalDistributedBus)->publishEvent(
            'userService.billing.DetailsWereChanged',
            'User changed billing address',
            metadata: ['token' => '123']
        );

        $this->assertNull($ticketService->getMessageChannel($ticketChannelName)->receive());
        $this->assertNotNull($orderService->getMessageChannel($orderChannelName)->receive());
    }

    public function test_combining_distributed_bus_with_dynamic_message_channels(): void
    {
        $commandDistributedChannel = SimpleMessageChannelBuilder::createQueueChannel('distributed_ticket_command_channel');
        $eventDistributedChannel = SimpleMessageChannelBuilder::createQueueChannel('distributed_ticket_event_channel');

        $dynamicMessageChannel = DynamicMessageChannelBuilder::createNoStrategy('distributed_ticket_channel')
            ->withHeaderSendingStrategy(
                headerName: 'ecotone.distributed.payloadType',
                headerMapping: [
                    'command' => $commandDistributedChannel->getMessageChannelName(),
                    'event' => $eventDistributedChannel->getMessageChannelName(),
                ]
            )
            ->withInternalChannels([
                $commandDistributedChannel,
                $eventDistributedChannel,
            ]);
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Publisher'],
            [],
            [$dynamicMessageChannel],
            [
                DistributedServiceMap::initialize()
                    ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $dynamicMessageChannel->getMessageChannelName())
                    ->withEventMapping(channelName: $dynamicMessageChannel->getMessageChannelName(), subscriptionKeys: ['*']),
            ],
        );

        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Receiver', 'Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverOrder'], [new TicketServiceReceiver(), new OrderServiceReceiver()], [
            $commandDistributedChannel,
            $eventDistributedChannel,
        ]);

        $userService->getDistributedBus()->sendCommand(
            TestServiceName::TICKET_SERVICE,
            TicketServiceReceiver::CREATE_TICKET_ENDPOINT,
            'User changed billing address',
            metadata: [
                'token' => '123',
            ]
        );

        $this->assertNotNull($ticketService->getMessageChannel($commandDistributedChannel->getMessageChannelName())->receive());
        ;
        $this->assertNull($ticketService->getMessageChannel($eventDistributedChannel->getMessageChannelName())->receive());
        ;

        $userService->getDistributedBus()->publishEvent(
            'userService.billing.DetailsWereChanged',
            'User changed billing address',
            metadata: ['token' => '123']
        );

        $this->assertNull($ticketService->getMessageChannel($commandDistributedChannel->getMessageChannelName())->receive());
        $this->assertNotNull($ticketService->getMessageChannel($eventDistributedChannel->getMessageChannelName())->receive());
        ;
    }

    public function test_receiving_message_from_non_ecotone_application_manually_crafted(): void
    {
        $sharedQueueChannel = SimpleMessageChannelBuilder::createQueueChannel('distributed_ticket_channel');
        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Receiver'], [new TicketServiceReceiver()], $sharedQueueChannel);

        self::assertEquals(0, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));

        $messageChannel = $ticketService->getMessageChannel($sharedQueueChannel->getMessageChannelName());
        $messageChannel->send(
            MessageBuilder::withPayload('User changed billing address')
                ->setHeader(MessageHeaders::ROUTING_SLIP, DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE)
                ->setHeader(DistributedBusHeader::DISTRIBUTED_ROUTING_KEY, TicketServiceReceiver::CREATE_TICKET_ENDPOINT)
                ->setHeader(DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE, 'command')
                ->setHeader(MessageHeaders::CONTENT_TYPE, MediaType::TEXT_PLAIN)
                ->setHeader('token', '123')
                ->build()
        );

        $ticketService->run($sharedQueueChannel->getMessageChannelName(), ExecutionPollingMetadata::createWithTestingSetup());
        self::assertEquals(1, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));
        self::assertEquals(
            'User changed billing address',
            $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS)[0]->getPayload()
        );
        self::assertEquals(
            '123',
            $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS)[0]->getHeaders()->get('token')
        );
    }

    public function test_throws_exception_without_enterprise_licence(): void
    {
        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            configuration: ServiceConfiguration::createWithDefaults()
                ->withServiceName('test')
                ->withExtensionObjects([
                    DistributedServiceMap::initialize(),
                ]),
            pathToRootCatalog: __DIR__ . '/../../',
        );
    }

    public function test_it_throws_exception_when_no_service_name_is_given(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            configuration: ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    DistributedServiceMap::initialize(),
                ]),
            pathToRootCatalog: __DIR__ . '/../../',
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_intercepting_sending_messages(): void
    {
        $sharedQueueChannel = SimpleMessageChannelBuilder::createQueueChannel($channelName = 'distributed_ticket_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Publisher', 'Test\Ecotone\Messaging\Fixture\Distributed\DistributedSendInterceptor'],
            [new DistributedSendInterceptor()],
            $sharedQueueChannel,
            DistributedServiceMap::initialize()
                ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $channelName)
        );
        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\Receiver'], [new TicketServiceReceiver()], $sharedQueueChannel);

        $userService->getDistributedBus()->sendCommand(
            TestServiceName::TICKET_SERVICE,
            TicketServiceReceiver::CREATE_TICKET_ENDPOINT,
            'User changed billing address',
        );

        $ticketService->run($sharedQueueChannel->getMessageChannelName(), ExecutionPollingMetadata::createWithTestingSetup());
        self::assertEquals(1, $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT));
        self::assertEquals(
            '123a',
            $ticketService->sendQueryWithRouting(TicketServiceReceiver::GET_TICKETS)[0]->getHeaders()->get('extra')
        );
    }

    public function test_distributing_event_to_shared_channel(): void
    {
        $sharedChannel = SimpleMessageChannelBuilder::createStreamingChannel($channelName = 'distributed_events');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            [],
            [],
            $sharedChannel,
            DistributedServiceMap::initialize()
                ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $channelName)
                ->withEventMapping(channelName: $channelName, subscriptionKeys: ['*'])
        );
        $ticketService = $this->bootstrapEcotone(
            TestServiceName::TICKET_SERVICE,
            ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket'],
            [new \Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket\TicketServiceReceiver()],
            $sharedChannel,
            DistributedServiceMap::initialize()
                ->withCommandMapping(targetServiceName: TestServiceName::USER_SERVICE, channelName: $channelName)
                ->withEventMapping(channelName: $channelName, subscriptionKeys: ['*'])
        );

        self::assertEquals(0, $ticketService->sendQueryWithRouting(\Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket\TicketServiceReceiver::GET_TICKETS_COUNT));

        $userService->getDistributedBus()->publishEvent(
            'userService.userChangedBillingAddress',
            'User changed billing address',
            metadata: [
                'token' => '123',
            ]
        );

        $ticketService->run($channelName, ExecutionPollingMetadata::createWithTestingSetup());
        self::assertEquals(1, $ticketService->sendQueryWithRouting(\Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket\TicketServiceReceiver::GET_TICKETS_COUNT));
    }

    private function bootstrapEcotone(string $serviceName, array $namespaces, array $services, MessageChannelBuilder|array|null $sharedQueueChannel = null, null|array|DistributedServiceMap $extensionObjects = null): FlowTestSupport
    {
        $extensionObjects = $extensionObjects instanceof DistributedServiceMap ? [$extensionObjects] : $extensionObjects;

        return EcotoneLite::bootstrapFlowTesting(
            containerOrAvailableServices: $services,
            configuration: ServiceConfiguration::createWithDefaults()
                ->withServiceName($serviceName)
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withExtensionObjects(array_merge(is_array($sharedQueueChannel) ? $sharedQueueChannel : [
                    $sharedQueueChannel ?? SimpleMessageChannelBuilder::createQueueChannel($serviceName),
                ], $extensionObjects ?: []))
                ->withNamespaces($namespaces),
            pathToRootCatalog: __DIR__ . '/../../',
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }


    /**
     * This test verifies that streaming channels can be used for distributed event publishing,
     * where one service publishes events and multiple consuming services each have their own
     * consumer group to track their position independently.
     */
    public function test_streaming_channel_with_distributed_bus_using_service_map(): void
    {
        $positionTracker = new InMemoryConsumerPositionTracker();

        // Publisher service
        $publisher = new class () {
            #[CommandHandler('publish.event')]
            public function publish(string $payload, \Ecotone\Modelling\EventBus $eventBus): void
            {
                $eventBus->publish($payload);
            }
        };

        // Consumer 1 in service 1
        $consumer1 = new class () {
            private array $consumed = [];

            #[Distributed]
            #[EventHandler('distributed.event', endpointId: 'consumer1')]
            public function handle(string $payload): void
            {
                $this->consumed[] = $payload;
            }

            #[QueryHandler('getConsumed1')]
            public function getConsumed(): array
            {
                return $this->consumed;
            }
        };

        // Consumer 2 in service 2
        $consumer2 = new class () {
            private array $consumed = [];

            #[Distributed]
            #[EventHandler('distributed.event', endpointId: 'consumer2')]
            public function handle(string $payload): void
            {
                $this->consumed[] = $payload;
            }

            #[QueryHandler('getConsumed2')]
            public function getConsumed(): array
            {
                return $this->consumed;
            }
        };

        $publisherStreamingChannel = SimpleMessageChannelBuilder::createStreamingChannel($channelName = 'distributed_events');
        $service0Name = 'service0';
        $service1Name = 'service1';
        $service2Name = 'service2';
        $queueChannelService0 = SimpleMessageChannelBuilder::createQueueChannel($service0Name);
        $queueChannelService1 = SimpleMessageChannelBuilder::createQueueChannel($service1Name);
        $queueChannelService2 = SimpleMessageChannelBuilder::createQueueChannel($service2Name);
        $sharedDistributedMap = DistributedServiceMap::initialize()
            ->withCommandMapping(targetServiceName: 'service0_distributed_events', channelName: $publisherStreamingChannel->getMessageChannelName())
            ->withCommandMapping(targetServiceName: $service0Name, channelName: $queueChannelService0->getMessageChannelName())
            ->withCommandMapping(targetServiceName: $service1Name, channelName: $queueChannelService1->getMessageChannelName())
            ->withCommandMapping(targetServiceName: $service2Name, channelName: $queueChannelService2->getMessageChannelName())
            // Events are published to the streaming channel for all services to consume
            ->withEventMapping(channelName: $publisherStreamingChannel->getMessageChannelName(), subscriptionKeys: ['*']);

        // Publisher service
        $publisherService = EcotoneLite::bootstrapFlowTesting(
            [$publisher::class],
            [$publisher, ConsumerPositionTracker::class => $positionTracker],
            ServiceConfiguration::createWithDefaults()
                ->withServiceName($service0Name)
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withExtensionObjects([
                    $publisherStreamingChannel,
                    $queueChannelService0,
                    $queueChannelService1,
                    $queueChannelService2,
                    $sharedDistributedMap,
                ]),
            pathToRootCatalog: __DIR__ . '/../../',
            licenceKey: LicenceTesting::VALID_LICENCE
        );

        // Consumer service 1
        $consumerService1 = EcotoneLite::bootstrapFlowTesting(
            [$consumer1::class],
            [$consumer1, ConsumerPositionTracker::class => $positionTracker],
            ServiceConfiguration::createWithDefaults()
                ->withServiceName($service1Name)
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withExtensionObjects([
                    $publisherStreamingChannel,
                    $queueChannelService0,
                    $queueChannelService1,
                    $queueChannelService2,
                    $sharedDistributedMap,
                ]),
            pathToRootCatalog: __DIR__ . '/../../',
            licenceKey: LicenceTesting::VALID_LICENCE
        );

        // Consumer service 2
        $consumerService2 = EcotoneLite::bootstrapFlowTesting(
            [$consumer2::class],
            [$consumer2, ConsumerPositionTracker::class => $positionTracker],
            ServiceConfiguration::createWithDefaults()
                ->withServiceName($service2Name)
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withExtensionObjects([
                    $publisherStreamingChannel,
                    $queueChannelService0,
                    $queueChannelService1,
                    $queueChannelService2,
                    $sharedDistributedMap,
                ]),
            pathToRootCatalog: __DIR__ . '/../../',
            licenceKey: LicenceTesting::VALID_LICENCE
        );

        // Publish events
        $publisherService->getDistributedBus()->publishEvent('distributed.event', 'event1');
        $publisherService->getDistributedBus()->publishEvent('distributed.event', 'event2');
        $publisherService->getDistributedBus()->publishEvent('distributed.event', 'event3');

        // Both consumers should receive all events independently
        $consumerService1->run($channelName, ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 10));
        $consumerService2->run($channelName, ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 10));

        $this->assertEquals(['event1', 'event2', 'event3'], $consumerService1->sendQueryWithRouting('getConsumed1'));
        $this->assertEquals(['event1', 'event2', 'event3'], $consumerService2->sendQueryWithRouting('getConsumed2'));
        $this->assertNull($publisherService->getMessageChannel($service0Name)->receive());
        $this->assertNull($consumerService1->getMessageChannel($service1Name)->receive());
        $this->assertNull($consumerService2->getMessageChannel($service2Name)->receive());
    }

    public function test_cannot_use_legacy_with_service_mapping_after_with_command_mapping(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Cannot use withServiceMapping() after withCommandMapping() or withEventMapping()');

        DistributedServiceMap::initialize()
            ->withCommandMapping('service1', 'channel1')
            ->withServiceMapping('service2', 'channel2');
    }

    public function test_cannot_use_legacy_with_service_mapping_after_with_event_mapping(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Cannot use withServiceMapping() after withCommandMapping() or withEventMapping()');

        DistributedServiceMap::initialize()
            ->withEventMapping('channel1', ['*'])
            ->withServiceMapping('service2', 'channel2');
    }

    public function test_cannot_use_with_command_mapping_after_legacy_with_service_mapping(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Cannot use withCommandMapping() after withServiceMapping()');

        DistributedServiceMap::initialize()
            ->withServiceMapping('service1', 'channel1')
            ->withCommandMapping('service2', 'channel2');
    }

    public function test_cannot_use_with_event_mapping_after_legacy_with_service_mapping(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Cannot use withEventMapping() after withServiceMapping()');

        DistributedServiceMap::initialize()
            ->withServiceMapping('service1', 'channel1')
            ->withEventMapping('channel2', ['*']);
    }

    public function test_it_publishes_event_only_to_channel_when_source_service_is_in_include_list(): void
    {
        $distributedTicketQueue = SimpleMessageChannelBuilder::createQueueChannel($ticketChannelName = 'distributed_ticket_channel');
        $distributedUserQueue = SimpleMessageChannelBuilder::createQueueChannel($userChannelName = 'distributed_user_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            [],
            [],
            [$distributedTicketQueue, $distributedUserQueue],
            DistributedServiceMap::initialize()
                ->withCommandMapping(targetServiceName: TestServiceName::TICKET_SERVICE, channelName: $ticketChannelName)
                ->withCommandMapping(targetServiceName: TestServiceName::USER_SERVICE, channelName: $userChannelName)
                ->withEventMapping(channelName: $ticketChannelName, subscriptionKeys: ['*'], includePublishingServices: [TestServiceName::TICKET_SERVICE])
                ->withEventMapping(channelName: $userChannelName, subscriptionKeys: ['*'])
        );
        $ticketService = $this->bootstrapEcotone(TestServiceName::TICKET_SERVICE, ['Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket'], [new \Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket\TicketServiceReceiver()], $distributedTicketQueue);

        $userService->getDistributedBus()->publishEvent(
            'userService.billing.DetailsWereChanged',
            'User changed billing address',
            metadata: ['token' => '123']
        );

        self::assertNull($ticketService->getMessageChannel($ticketChannelName)->receive());
        self::assertNotNull($userService->getMessageChannel($userChannelName)->receive());
    }

    public function test_cannot_use_both_exclude_and_include_publishing_services(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Cannot use both 'excludePublishingServices' and 'includePublishingServices' in the same event mapping for channel 'channel1'. These parameters are mutually exclusive - use either exclude (blacklist) or include (whitelist), not both.");

        DistributedServiceMap::initialize()
            ->withEventMapping('channel1', ['*'], excludePublishingServices: ['service1'], includePublishingServices: ['service2']);
    }
}
