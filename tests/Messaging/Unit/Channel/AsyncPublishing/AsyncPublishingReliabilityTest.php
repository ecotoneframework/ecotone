<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel\AsyncPublishing;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\BatchMessage;
use Ecotone\Messaging\Channel\AsyncPublishing\AsyncPublishingRegistry;
use Ecotone\Messaging\Channel\AsyncPublishing\DeliveryFuture;
use Ecotone\Messaging\Channel\AsyncPublishing\PublishingFailedException;
use Ecotone\Messaging\Channel\MessageChannelInterceptorAdapter;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\Logger\LoggingService;
use Ecotone\Messaging\MessagePublisher;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Attribute\CommandHandler;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\AsyncPublishing\AsyncOrderSubscriber;
use Test\Ecotone\Messaging\Fixture\AsyncPublishing\FakeTransactionModule;
use Test\Ecotone\Messaging\Fixture\AsyncPublishing\InMemoryAsyncOutboundAdapter;
use Test\Ecotone\Messaging\Fixture\AsyncPublishing\InMemoryAsyncPublisherModule;
use Test\Ecotone\Messaging\Fixture\AsyncPublishing\InMemoryAsyncPublishingChannelBuilder;
use Test\Ecotone\Messaging\Fixture\AsyncPublishing\InMemoryPendingDelivery;
use Test\Ecotone\Messaging\Fixture\AsyncPublishing\OperationsLog;
use Test\Ecotone\Messaging\Fixture\AsyncPublishing\OrderService;

/**
 * licence Apache-2.0
 * @internal
 */
final class AsyncPublishingReliabilityTest extends TestCase
{
    public function test_future_throwing_during_await_keeps_reporting_failure_on_subsequent_resolves(): void
    {
        $throwingDelivery = new InMemoryPendingDelivery(
            MessageBuilder::withPayload('order')->build(),
            throwOnAwait: true,
        );
        $future = DeliveryFuture::forPendingDeliveries([$throwingDelivery]);

        $firstResolveException = null;
        try {
            $future->resolve();
        } catch (PublishingFailedException $exception) {
            $firstResolveException = $exception;
        }
        $this->assertNotNull($firstResolveException);

        $this->expectException(PublishingFailedException::class);

        $future->resolve();
    }

    public function test_future_does_not_reawait_deliveries_already_awaited_by_interceptor_scope(): void
    {
        $alreadyAwaitedDelivery = new InMemoryPendingDelivery(MessageBuilder::withPayload('order')->build());
        $alreadyAwaitedDelivery->awaitDelivery();

        DeliveryFuture::forPendingDeliveries([$alreadyAwaitedDelivery])->resolve();

        $this->assertSame(1, $alreadyAwaitedDelivery->awaitCalls());
    }

    public function test_failed_deliveries_routed_to_async_error_channel_are_awaited_before_commit(): void
    {
        $operationsLog = new OperationsLog();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, AsyncOrderSubscriber::class, FakeTransactionModule::class],
            [new OrderService($operationsLog), new AsyncOrderSubscriber(), OperationsLog::class => $operationsLog],
            ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                \Ecotone\Messaging\Channel\PollableChannel\GlobalPollableChannelConfiguration::createWithDefaults()->withErrorChannel('failure_channel'),
            ]),
            enableAsynchronousProcessing: [
                InMemoryAsyncPublishingChannelBuilder::create('async_orders'),
                InMemoryAsyncPublishingChannelBuilder::create('failure_channel'),
            ],
        );
        $ordersChannel = $ecotoneLite->getMessageChannel('async_orders');
        assert($ordersChannel instanceof MessageChannelInterceptorAdapter);
        $ordersChannel->getInternalMessageChannel()->failDeliveriesWith('broker not available');

        $ecotoneLite->sendCommandWithRoutingKey('order.place', 'espresso');

        $operations = $operationsLog->getOperations();
        $this->assertSame('transaction committed', $operations[count($operations) - 1]);
        $awaitedConfirmationsForFailedBatchAndEachErrorChannelMessage = count(array_filter($operations, fn (string $operation) => $operation === 'delivery confirmations awaited'));
        $this->assertSame(3, $awaitedConfirmationsForFailedBatchAndEachErrorChannelMessage);
    }

    public function test_future_of_delivery_flushed_by_backlog_limit_still_reports_failure(): void
    {
        $outboundAdapter = new InMemoryAsyncOutboundAdapter();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [InMemoryAsyncPublisherModule::class, InMemoryAsyncOutboundAdapter::class],
            [$outboundAdapter],
        );
        $publisher = $ecotoneLite->getGateway(InMemoryAsyncPublisherModule::PUBLISHER_REFERENCE);
        $outboundAdapter->failDeliveriesWith('broker down');

        $firstFuture = $publisher->asyncPublish('first order');
        for ($messageNumber = 0; $messageNumber < 1300; $messageNumber++) {
            $publisher->asyncPublish('unresolved order ' . $messageNumber);
        }
        $this->assertGreaterThan(0, $outboundAdapter->awaitedDeliveriesCount());

        $this->expectException(PublishingFailedException::class);

        $firstFuture->resolve();
    }

    public function test_unawaited_deliveries_of_all_registries_are_flushed_on_script_shutdown(): void
    {
        $scriptPath = tempnam(sys_get_temp_dir(), 'async_publishing_shutdown_flush_');
        file_put_contents($scriptPath, <<<'PHP'
            <?php

            require $argv[1];

            $unawaitedDelivery = new class implements \Ecotone\Messaging\Channel\AsyncPublishing\PendingDelivery {
                private bool $awaited = false;

                public function awaitDelivery(): \Ecotone\Messaging\Channel\AsyncPublishing\DeliveryResult
                {
                    $this->awaited = true;
                    echo 'flushed;';

                    return \Ecotone\Messaging\Channel\AsyncPublishing\DeliveryResult::successful();
                }

                public function isAwaited(): bool
                {
                    return $this->awaited;
                }
            };

            $firstRegistry = new \Ecotone\Messaging\Channel\AsyncPublishing\AsyncPublishingRegistry(new \Ecotone\Messaging\Handler\Logger\LoggingService());
            $firstRegistry->register('orders', $unawaitedDelivery);
            $secondRegistry = new \Ecotone\Messaging\Channel\AsyncPublishing\AsyncPublishingRegistry(new \Ecotone\Messaging\Handler\Logger\LoggingService());
            $secondRegistry->register('shipments', clone $unawaitedDelivery);

            echo 'script finished;';
            PHP);

        $output = shell_exec(sprintf(
            'php %s %s 2>&1',
            escapeshellarg($scriptPath),
            escapeshellarg($this->nearestComposerAutoloadPath()),
        ));
        unlink($scriptPath);

        $this->assertSame('script finished;flushed;flushed;', $output);
    }

    private function nearestComposerAutoloadPath(): string
    {
        for ($directory = __DIR__; $directory !== dirname($directory); $directory = dirname($directory)) {
            if (file_exists($directory . '/vendor/autoload.php')) {
                return $directory . '/vendor/autoload.php';
            }
        }

        $this->fail('No composer autoload found above ' . __DIR__);
    }

    public function test_shutdown_flush_continues_when_one_delivery_throws(): void
    {
        $registry = new AsyncPublishingRegistry(new LoggingService());
        $throwingDelivery = new InMemoryPendingDelivery(MessageBuilder::withPayload('first order')->build(), throwOnAwait: true);
        $followingDelivery = new InMemoryPendingDelivery(MessageBuilder::withPayload('second order')->build());
        $registry->register('orders', $throwingDelivery);
        $registry->register('orders', $followingDelivery);

        $registry->flushUnawaitedDeliveries();

        $this->assertTrue($followingDelivery->isAwaited());
    }

    public function test_closing_scope_awaits_deliveries_left_unawaited_when_execution_fails_before_await(): void
    {
        $registry = new AsyncPublishingRegistry(new LoggingService());
        $registry->openScope();
        $delivery = new InMemoryPendingDelivery(MessageBuilder::withPayload('order')->build());
        $registry->register('orders', $delivery);

        $registry->closeScope();

        $this->assertTrue($delivery->isAwaited());
    }

    public function test_future_awaits_remaining_deliveries_when_earlier_delivery_throws(): void
    {
        $throwingDelivery = new InMemoryPendingDelivery(MessageBuilder::withPayload('first order')->build(), throwOnAwait: true);
        $followingDelivery = new InMemoryPendingDelivery(MessageBuilder::withPayload('second order')->build());
        $future = DeliveryFuture::forPendingDeliveries([$throwingDelivery, $followingDelivery]);

        try {
            $future->resolve();
        } catch (PublishingFailedException) {
        }

        $this->assertTrue($followingDelivery->isAwaited());
    }

    public function test_one_failing_batch_among_many_published_in_command_handler_rolls_back_transaction(): void
    {
        $operationsLog = new OperationsLog();
        $outboundAdapter = new InMemoryAsyncOutboundAdapter();
        $commandHandler = new class () {
            #[CommandHandler('order.placeAllBatches')]
            public function handle(string $order, #[Reference(InMemoryAsyncPublisherModule::PUBLISHER_REFERENCE)] MessagePublisher $publisher): void
            {
                $publisher->asyncPublish(BatchMessage::constructEmpty()->append($order . ' first batch'));
                $publisher->asyncPublish(BatchMessage::constructEmpty()->append($order . ' poisoned batch'));
                $publisher->asyncPublish(BatchMessage::constructEmpty()->append($order . ' third batch'));
            }
        };
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$commandHandler::class, InMemoryAsyncPublisherModule::class, InMemoryAsyncOutboundAdapter::class, FakeTransactionModule::class],
            [$commandHandler, $outboundAdapter, OperationsLog::class => $operationsLog],
        );
        $outboundAdapter->failDeliveriesContaining('poisoned', 'broker rejected the batch');

        $commandFailed = false;
        try {
            $ecotoneLite->sendCommandWithRoutingKey('order.placeAllBatches', 'espresso');
        } catch (PublishingFailedException) {
            $commandFailed = true;
        }

        $this->assertTrue($commandFailed);
        $operations = $operationsLog->getOperations();
        $this->assertSame('transaction rolled back', $operations[count($operations) - 1]);
        $this->assertSame(3, $outboundAdapter->awaitedDeliveriesCount());
    }

    public function test_unresolved_publisher_futures_above_backlog_limit_are_flushed(): void
    {
        $outboundAdapter = new InMemoryAsyncOutboundAdapter();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [InMemoryAsyncPublisherModule::class, InMemoryAsyncOutboundAdapter::class],
            [$outboundAdapter],
        );
        $publisher = $ecotoneLite->getGateway(InMemoryAsyncPublisherModule::PUBLISHER_REFERENCE);

        for ($messageNumber = 0; $messageNumber < 1300; $messageNumber++) {
            $publisher->asyncPublish('unresolved order ' . $messageNumber);
        }

        $this->assertGreaterThan(0, $outboundAdapter->awaitedDeliveriesCount());
        $this->assertLessThan(1300, $outboundAdapter->awaitedDeliveriesCount());
    }
}
