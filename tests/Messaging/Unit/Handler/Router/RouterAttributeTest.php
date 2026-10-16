<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Router;

use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Router;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\WrongHandlerAmountException;
use Ecotone\Messaging\Handler\DestinationResolutionException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class RouterAttributeTest extends TestCase
{
    public function test_routing_message_to_a_single_channel(): void
    {
        $handler = new CustomRouterHandler();
        $handler->channelsToPick = 'buyChannel';
        $ecotone = $this->bootstrap($handler);

        $ecotone->sendDirectToChannel(CustomRouterHandler::ROUTER_CHANNEL, 'some');

        $this->assertTrue($handler->buyChannelCalled);
        $this->assertFalse($handler->sellChannelCalled);
    }

    public function test_routing_message_to_multiple_channels(): void
    {
        $handler = new CustomRouterHandler();
        $handler->channelsToPick = ['buyChannel', 'sellChannel'];
        $ecotone = $this->bootstrap($handler);

        $ecotone->sendDirectToChannel(CustomRouterHandler::ROUTER_CHANNEL, 'some');

        $this->assertTrue($handler->buyChannelCalled);
        $this->assertTrue($handler->sellChannelCalled);
    }

    public function test_throwing_exception_when_resolution_is_required_but_router_picks_nothing(): void
    {
        $handler = new CustomRouterHandler();
        $handler->channelsToPick = [];
        $ecotone = $this->bootstrap($handler);

        $this->expectException(DestinationResolutionException::class);

        $ecotone->sendDirectToChannel(CustomRouterHandler::ROUTER_CHANNEL, 'some');
    }

    public function test_not_throwing_exception_when_resolution_is_not_required_and_router_picks_nothing(): void
    {
        $handler = new NotRequiredRouterHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [NotRequiredRouterHandler::class],
            [$handler],
            enabledModules: [],
        );

        $ecotone->sendDirectToChannel(NotRequiredRouterHandler::ROUTER_CHANNEL, 'some');

        $this->assertFalse($handler->buyChannelCalled);
    }

    public function test_a_router_sharing_its_direct_input_channel_with_another_handler_is_refused_naming_both(): void
    {
        $fixture = new class () {
            #[Router('router.shared')]
            public function pick(string $payload): string
            {
                return 'router.target';
            }

            #[InternalHandler('router.shared')]
            public function handle(string $payload): void
            {
            }

            #[InternalHandler('router.target')]
            public function target(string $payload): void
            {
            }
        };

        try {
            EcotoneLite::bootstrapFlowTesting(
                [$fixture::class],
                [$fixture],
                enabledModules: [],
            );
            $this->fail('A direct channel with two subscribed handlers was not refused at bootstrap');
        } catch (WrongHandlerAmountException $exception) {
            $this->assertStringContainsString('Direct channel `router.shared` delivers a message to one handler only', $exception->getMessage());
            $this->assertStringContainsString('Router - invocation of ' . $fixture::class . '::pick', $exception->getMessage());
            $this->assertStringContainsString($fixture::class . '::handle', $exception->getMessage());
            $this->assertStringContainsString("SimpleMessageChannelBuilder::createPublishSubscribeChannel('router.shared')", $exception->getMessage());
        }
    }

    public function test_two_handlers_sharing_a_direct_input_channel_are_refused_at_bootstrap_naming_both(): void
    {
        $fixture = new class () {
            #[InternalHandler('shared.channel')]
            public function first(string $payload): void
            {
            }

            #[InternalHandler('shared.channel')]
            public function second(string $payload): void
            {
            }
        };

        try {
            EcotoneLite::bootstrapFlowTesting(
                [$fixture::class],
                [$fixture],
                enabledModules: [],
            );
            $this->fail('Two handlers on one direct channel were not refused at bootstrap');
        } catch (WrongHandlerAmountException $exception) {
            $this->assertStringContainsString('Direct channel `shared.channel` delivers a message to one handler only', $exception->getMessage());
            $this->assertStringContainsString($fixture::class . '::first', $exception->getMessage());
            $this->assertStringContainsString($fixture::class . '::second', $exception->getMessage());
            $this->assertStringContainsString("Give one of them its own input channel, or register the channel with SimpleMessageChannelBuilder::createPublishSubscribeChannel('shared.channel') so that each of them receives the message.", $exception->getMessage());
        }
    }

    public function test_two_handlers_sharing_a_publish_subscribe_channel_both_receive_the_message(): void
    {
        $fixture = new class () {
            public array $received = [];

            #[InternalHandler('shared.channel')]
            public function first(string $payload): void
            {
                $this->received[] = 'first';
            }

            #[InternalHandler('shared.channel')]
            public function second(string $payload): void
            {
                $this->received[] = 'second';
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$fixture::class],
            [$fixture],
            extensionObjects: [SimpleMessageChannelBuilder::createPublishSubscribeChannel('shared.channel')],
            enabledModules: [],
        );

        $ecotone->sendDirectToChannel('shared.channel', 'some');

        $this->assertSame(['first', 'second'], $fixture->received);
    }

    private function bootstrap(CustomRouterHandler $handler)
    {
        return EcotoneLite::bootstrapFlowTesting(
            [CustomRouterHandler::class],
            [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('unused')],
            enabledModules: [],
        );
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class CustomRouterHandler
{
    public const ROUTER_CHANNEL = 'router.input';

    public string|array $channelsToPick = [];
    public bool $buyChannelCalled = false;
    public bool $sellChannelCalled = false;

    #[Router(self::ROUTER_CHANNEL)]
    public function pick(string $message): string|array
    {
        return $this->channelsToPick;
    }

    #[InternalHandler('buyChannel')]
    public function handleBuy(): void
    {
        $this->buyChannelCalled = true;
    }

    #[InternalHandler('sellChannel')]
    public function handleSell(): void
    {
        $this->sellChannelCalled = true;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class NotRequiredRouterHandler
{
    public const ROUTER_CHANNEL = 'router.notRequired.input';

    public bool $buyChannelCalled = false;

    #[Router(self::ROUTER_CHANNEL, isResolutionRequired: false)]
    public function pick(string $message): array
    {
        return [];
    }

    #[InternalHandler('buyChannel')]
    public function handleBuy(): void
    {
        $this->buyChannelCalled = true;
    }
}
