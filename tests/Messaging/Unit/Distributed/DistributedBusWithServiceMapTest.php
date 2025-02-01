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
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Api\Distribution\DistributedBusHeader;
use Ecotone\Modelling\Api\Distribution\DistributedServiceMap;
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
final class DistributedBusWithServiceMapTest extends TestCase
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
                ->withServiceMapping(serviceName: TestServiceName::TICKET_SERVICE, channelName: $channelName)
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
                ->withServiceMapping(serviceName: TestServiceName::TICKET_SERVICE, channelName: $channelName)
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
                ->withServiceMapping(serviceName: TestServiceName::TICKET_SERVICE, channelName: $channelName)
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
                                            ->withServiceMapping(serviceName: TestServiceName::TICKET_SERVICE, channelName: 'not_existing_channel')
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
                ->withServiceMapping(serviceName: TestServiceName::TICKET_SERVICE, channelName: $channelName)
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
                ->withServiceMapping(serviceName: TestServiceName::TICKET_SERVICE, channelName: $channelName)
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
                ->withServiceMapping(serviceName: TestServiceName::TICKET_SERVICE, channelName: $ticketChannelName)
                ->withServiceMapping(serviceName: TestServiceName::ORDER_SERVICE, channelName: $orderChannelName)
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

    public function test_it_does_not_publish_event_to_publishing_service_when_in_service_map(): void
    {
        $distributedTicketQueue = SimpleMessageChannelBuilder::createQueueChannel($ticketChannelName = 'distributed_ticket_channel');
        $distributedUserQueue = SimpleMessageChannelBuilder::createQueueChannel($userChannelName = 'distributed_order_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            [],
            [],
            [$distributedTicketQueue, $distributedUserQueue],
            DistributedServiceMap::initialize()
                ->withServiceMapping(serviceName: TestServiceName::TICKET_SERVICE, channelName: $ticketChannelName)
                ->withServiceMapping(serviceName: TestServiceName::USER_SERVICE, channelName: $userChannelName)
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
                ->withServiceMapping(serviceName: TestServiceName::TICKET_SERVICE, channelName: $ticketChannelName, subscriptionRoutingKeys: ['userService.*'])
                ->withServiceMapping(serviceName: TestServiceName::ORDER_SERVICE, channelName: $orderChannelName, subscriptionRoutingKeys: ['ticketService.*'])
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
                ->withServiceMapping(serviceName: TestServiceName::TICKET_SERVICE, channelName: $ticketChannelName, subscriptionRoutingKeys: ['userService.*', '*'])
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

    public function test_not_receiving_for_empty_array(): void
    {
        $distributedTicketQueue = SimpleMessageChannelBuilder::createQueueChannel($ticketChannelName = 'distributed_ticket_channel');
        $userService = $this->bootstrapEcotone(
            TestServiceName::USER_SERVICE,
            [],
            [],
            [$distributedTicketQueue],
            DistributedServiceMap::initialize()
                ->withServiceMapping(serviceName: TestServiceName::TICKET_SERVICE, channelName: $ticketChannelName, subscriptionRoutingKeys: [])
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
                ->withServiceMapping(serviceName: TestServiceName::TICKET_SERVICE, channelName: $channelName)
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
                   ->withServiceMapping(serviceName: TestServiceName::TICKET_SERVICE, channelName: $ticketChannelName),
                DistributedServiceMap::initialize(referenceName: $externalDistributedBus = DistributedBus::class . '-external')
                    ->withServiceMapping(serviceName: TestServiceName::ORDER_SERVICE, channelName: $orderChannelName),
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
                    ->withServiceMapping(serviceName: TestServiceName::TICKET_SERVICE, channelName: $dynamicMessageChannel->getMessageChannelName()),
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
                ->withServiceMapping(serviceName: TestServiceName::TICKET_SERVICE, channelName: $channelName)
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
}
