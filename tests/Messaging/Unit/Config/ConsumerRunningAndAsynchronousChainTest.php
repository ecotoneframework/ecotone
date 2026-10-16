<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ConsumerRunningAndAsynchronousChainTest extends TestCase
{
    public function test_running_an_event_driven_consumer_processes_the_message_immediately(): void
    {
        $handler = new DirectlyConsumedHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [DirectlyConsumedHandler::class],
            [$handler],
            enabledModules: [],
        );

        $ecotone->sendDirectToChannel(DirectlyConsumedHandler::CHANNEL, 'a');

        $this->assertTrue($handler->wasCalled);
    }

    public function test_running_a_pollable_consumer_processes_a_previously_queued_message(): void
    {
        $handler = new SingleHopAsyncHandler();
        $ecotone = $this->bootstrapSingleHop($handler);

        $ecotone->sendCommandWithRouting(SingleHopAsyncHandler::ROUTING_KEY, 2);

        $this->assertNull($handler->lastResult);
        $ecotone->run(SingleHopAsyncHandler::CHANNEL, ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false));
        $this->assertSame(3, $handler->lastResult);
    }

    public function test_throws_when_running_a_consumer_that_does_not_exist(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [],
            [],
            enabledModules: [],
        );

        $this->expectException(InvalidArgumentException::class);

        $ecotone->run('some');
    }

    public function test_application_bootstrap_throws_when_asynchronous_channel_has_no_consumer_registered(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrap(
            [UnregisteredAsyncChannelHandler::class],
            [new UnregisteredAsyncChannelHandler()],
            enabledModules: [],
        );
    }

    public function test_message_flows_through_two_chained_asynchronous_channels_in_order(): void
    {
        $handler = new TwoHopAsyncHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [TwoHopAsyncHandler::class],
            [$handler],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel(TwoHopAsyncHandler::CHANNEL_ONE),
                SimpleMessageChannelBuilder::createQueueChannel(TwoHopAsyncHandler::CHANNEL_TWO),
            ],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting(TwoHopAsyncHandler::ROUTING_KEY, 2);

        $this->assertNull($handler->lastResult);
        $ecotone->run(TwoHopAsyncHandler::CHANNEL_ONE, ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false));
        $this->assertNull($handler->lastResult);
        $ecotone->run(TwoHopAsyncHandler::CHANNEL_TWO, ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false));
        $this->assertSame(3, $handler->lastResult);
    }

    public function test_message_flows_through_three_chained_asynchronous_channels_in_order(): void
    {
        $handler = new ThreeHopAsyncHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ThreeHopAsyncHandler::class],
            [$handler],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel(ThreeHopAsyncHandler::CHANNEL_ONE),
                SimpleMessageChannelBuilder::createQueueChannel(ThreeHopAsyncHandler::CHANNEL_TWO),
                SimpleMessageChannelBuilder::createQueueChannel(ThreeHopAsyncHandler::CHANNEL_THREE),
            ],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting(ThreeHopAsyncHandler::ROUTING_KEY, 2);

        $this->assertNull($handler->lastResult);
        $ecotone->run(ThreeHopAsyncHandler::CHANNEL_ONE, ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false));
        $this->assertNull($handler->lastResult);
        $ecotone->run(ThreeHopAsyncHandler::CHANNEL_TWO, ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false));
        $this->assertNull($handler->lastResult);
        $ecotone->run(ThreeHopAsyncHandler::CHANNEL_THREE, ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false));
        $this->assertSame(3, $handler->lastResult);
    }

    private function bootstrapSingleHop(SingleHopAsyncHandler $handler)
    {
        return EcotoneLite::bootstrapFlowTesting(
            [SingleHopAsyncHandler::class],
            [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel(SingleHopAsyncHandler::CHANNEL)],
            enabledModules: [],
        );
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class DirectlyConsumedHandler
{
    public const CHANNEL = 'consumerRunning.direct';

    public bool $wasCalled = false;

    #[InternalHandler(self::CHANNEL)]
    public function handle(string $payload): void
    {
        $this->wasCalled = true;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class SingleHopAsyncHandler
{
    public const CHANNEL = 'consumerRunning.singleHop';
    public const ROUTING_KEY = 'consumerRunning.singleHop.handle';

    public ?int $lastResult = null;

    #[Asynchronous(self::CHANNEL)]
    #[CommandHandler(self::ROUTING_KEY, 'singleHopHandler')]
    public function handle(int $amount): void
    {
        $this->lastResult = $amount + 1;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class UnregisteredAsyncChannelHandler
{
    #[Asynchronous('missingChannel')]
    #[CommandHandler('consumerRunning.missing', 'missingChannelHandler')]
    public function handle(int $amount): void
    {
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class TwoHopAsyncHandler
{
    public const CHANNEL_ONE = 'consumerRunning.twoHop.one';
    public const CHANNEL_TWO = 'consumerRunning.twoHop.two';
    public const ROUTING_KEY = 'consumerRunning.twoHop.handle';

    public ?int $lastResult = null;

    #[Asynchronous([self::CHANNEL_ONE, self::CHANNEL_TWO])]
    #[CommandHandler(self::ROUTING_KEY, 'twoHopHandler')]
    public function handle(int $amount): void
    {
        $this->lastResult = $amount + 1;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ThreeHopAsyncHandler
{
    public const CHANNEL_ONE = 'consumerRunning.threeHop.one';
    public const CHANNEL_TWO = 'consumerRunning.threeHop.two';
    public const CHANNEL_THREE = 'consumerRunning.threeHop.three';
    public const ROUTING_KEY = 'consumerRunning.threeHop.handle';

    public ?int $lastResult = null;

    #[Asynchronous([self::CHANNEL_ONE, self::CHANNEL_TWO, self::CHANNEL_THREE])]
    #[CommandHandler(self::ROUTING_KEY, 'threeHopHandler')]
    public function handle(int $amount): void
    {
        $this->lastResult = $amount + 1;
    }
}
