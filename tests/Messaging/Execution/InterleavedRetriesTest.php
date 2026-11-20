<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Execution;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\InstantRetryConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Fiber;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * licence Apache-2.0
 * @internal
 */
final class InterleavedRetriesTest extends TestCase
{
    public function test_command_sent_while_another_execution_is_inside_its_command_bus_retry_is_retried_on_its_own(): void
    {
        $handlers = new class () {
            private array $attempts = ['order.place' => 0, 'payment.charge' => 0];

            #[CommandHandler('order.place')]
            public function place(): void
            {
                $this->attempts['order.place']++;
                Fiber::suspend();
            }

            #[CommandHandler('payment.charge')]
            public function charge(): void
            {
                $this->attempts['payment.charge']++;
                if ($this->attempts['payment.charge'] === 1) {
                    throw new RuntimeException('Payment gateway is temporarily unavailable');
                }
            }

            #[QueryHandler('attempts')]
            public function attempts(): array
            {
                return $this->attempts;
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handlers::class],
            [$handlers],
            extensionObjects: [InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true, 2)],
        );

        $suspended = new Fiber(fn () => $ecotone->sendCommandWithRouting('order.place'));
        $suspended->start();
        $ecotone->sendCommandWithRouting('payment.charge');
        $suspended->resume();

        $this->assertTrue($suspended->isTerminated());
        $this->assertSame(['order.place' => 1, 'payment.charge' => 2], $ecotone->sendQueryWithRouting('attempts'));
    }

    public function test_message_consumed_while_another_consumer_is_inside_its_asynchronous_endpoint_retry_is_retried_on_its_own(): void
    {
        $handlers = new class () {
            private array $attempts = ['order.place' => 0, 'payment.charge' => 0];

            #[Asynchronous('orders')]
            #[CommandHandler('order.place', endpointId: 'order.place.endpoint')]
            public function place(): void
            {
                $this->attempts['order.place']++;
                Fiber::suspend();
            }

            #[Asynchronous('payments')]
            #[CommandHandler('payment.charge', endpointId: 'payment.charge.endpoint')]
            public function charge(): void
            {
                $this->attempts['payment.charge']++;
                if ($this->attempts['payment.charge'] === 1) {
                    throw new RuntimeException('Payment gateway is temporarily unavailable');
                }
            }

            #[QueryHandler('attempts')]
            public function attempts(): array
            {
                return $this->attempts;
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handlers::class],
            [$handlers],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('orders'),
                SimpleMessageChannelBuilder::createQueueChannel('payments'),
                InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(true, 2),
            ],
        );
        $ecotone->sendCommandWithRouting('order.place');
        $ecotone->sendCommandWithRouting('payment.charge');

        $suspended = new Fiber(fn () => $ecotone->run('orders', ExecutionPollingMetadata::createWithTestingSetup()));
        $suspended->start();
        $ecotone->run('payments', ExecutionPollingMetadata::createWithTestingSetup());
        $suspended->resume();

        $this->assertTrue($suspended->isTerminated());
        $this->assertSame(['order.place' => 1, 'payment.charge' => 2], $ecotone->sendQueryWithRouting('attempts'));
    }
}
