<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Execution;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\EcotoneLite;
use Fiber;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * licence Apache-2.0
 * @internal
 */
final class InterleavedExecutionsTest extends TestCase
{
    public function test_headers_of_a_suspended_execution_are_not_propagated_into_an_interleaved_one(): void
    {
        $orders = new class () {
            private array $tenantSeenByEventHandler = [];

            #[CommandHandler('order.place')]
            public function place(array $command, EventBus $eventBus): void
            {
                if ($command['suspend']) {
                    Fiber::suspend();
                }

                $eventBus->publish(new OrderWasPlaced($command['orderId']));
            }

            #[EventHandler]
            public function when(OrderWasPlaced $event, #[Header('tenant')] ?string $tenant = null): void
            {
                $this->tenantSeenByEventHandler[$event->orderId] = $tenant;
            }

            #[QueryHandler('order.tenantSeenByEventHandler')]
            public function tenantSeenByEventHandler(): array
            {
                return $this->tenantSeenByEventHandler;
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting([$orders::class], [$orders]);

        $suspended = new Fiber(fn () => $ecotone->sendCommandWithRouting('order.place', ['orderId' => 'suspended', 'suspend' => true], metadata: ['tenant' => 'tenant-of-suspended']));
        $suspended->start();
        $ecotone->sendCommandWithRouting('order.place', ['orderId' => 'interleaved', 'suspend' => false]);
        $suspended->resume();

        $this->assertSame(
            ['interleaved' => null, 'suspended' => 'tenant-of-suspended'],
            $ecotone->sendQueryWithRouting('order.tenantSeenByEventHandler')
        );
    }

    public function test_fiber_started_by_a_handler_does_not_propagate_the_headers_of_the_message_being_handled(): void
    {
        $orders = new class () {
            private array $tenantSeenByEventHandler = [];

            #[CommandHandler('order.placeFromFiber')]
            public function placeFromFiber(array $command, EventBus $eventBus): void
            {
                (new Fiber(static fn () => $eventBus->publish(new OrderWasPlaced($command['orderId']))))->start();
            }

            #[EventHandler]
            public function when(OrderWasPlaced $event, #[Header('tenant')] ?string $tenant = null): void
            {
                $this->tenantSeenByEventHandler[$event->orderId] = $tenant;
            }

            #[QueryHandler('order.tenantSeenByEventHandler')]
            public function tenantSeenByEventHandler(): array
            {
                return $this->tenantSeenByEventHandler;
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting([$orders::class], [$orders]);

        $ecotone->sendCommandWithRouting('order.placeFromFiber', ['orderId' => 'placed by fiber'], metadata: ['tenant' => 'tenant-of-handler']);

        $this->assertSame(['placed by fiber' => null], $ecotone->sendQueryWithRouting('order.tenantSeenByEventHandler'));
    }

    public function test_two_suspended_executions_each_propagate_their_own_headers(): void
    {
        $orders = new class () {
            private array $tenantSeenByEventHandler = [];

            #[CommandHandler('order.place')]
            public function place(array $command, EventBus $eventBus): void
            {
                Fiber::suspend();

                $eventBus->publish(new OrderWasPlaced($command['orderId']));
            }

            #[EventHandler]
            public function when(OrderWasPlaced $event, #[Header('tenant')] ?string $tenant = null): void
            {
                $this->tenantSeenByEventHandler[$event->orderId] = $tenant;
            }

            #[QueryHandler('order.tenantSeenByEventHandler')]
            public function tenantSeenByEventHandler(): array
            {
                return $this->tenantSeenByEventHandler;
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting([$orders::class], [$orders]);

        $first = new Fiber(fn () => $ecotone->sendCommandWithRouting('order.place', ['orderId' => 'first'], metadata: ['tenant' => 'tenant-of-first']));
        $second = new Fiber(fn () => $ecotone->sendCommandWithRouting('order.place', ['orderId' => 'second'], metadata: ['tenant' => 'tenant-of-second']));
        $first->start();
        $second->start();
        $first->resume();
        $second->resume();

        $this->assertSame(
            ['first' => 'tenant-of-first', 'second' => 'tenant-of-second'],
            $ecotone->sendQueryWithRouting('order.tenantSeenByEventHandler')
        );
    }

    public function test_message_collected_by_an_interleaved_execution_is_sent_when_the_suspended_execution_fails(): void
    {
        $orders = new class () {
            private array $notifiedOrders = [];

            #[CommandHandler('order.place')]
            public function place(array $command, EventBus $eventBus): void
            {
                $eventBus->publish(new OrderWasPlaced($command['orderId']));

                if ($command['suspendAndFail']) {
                    Fiber::suspend();

                    throw new RuntimeException('Order was refused');
                }
            }

            #[Asynchronous('notifications')]
            #[EventHandler(endpointId: 'order.notify')]
            public function notify(OrderWasPlaced $event): void
            {
                $this->notifiedOrders[] = $event->orderId;
            }

            #[QueryHandler('order.notified')]
            public function notified(): array
            {
                return $this->notifiedOrders;
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$orders::class],
            [$orders],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('notifications')],
        );

        $suspended = new Fiber(function () use ($ecotone): void {
            try {
                $ecotone->sendCommandWithRouting('order.place', ['orderId' => 'refused', 'suspendAndFail' => true]);
            } catch (RuntimeException) {
            }
        });
        $suspended->start();
        $ecotone->sendCommandWithRouting('order.place', ['orderId' => 'accepted', 'suspendAndFail' => false]);
        $suspended->resume();
        $ecotone->run('notifications', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertSame(['accepted'], $ecotone->sendQueryWithRouting('order.notified'));
    }
}

final class OrderWasPlaced
{
    public function __construct(public string $orderId)
    {
    }
}
