<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel;

use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\MessageDispatchingException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class DirectAndPublishSubscribeChannelTest extends TestCase
{
    public function test_direct_channel_dispatches_to_its_single_subscribed_handler(): void
    {
        $handler = new DirectChannelHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([DirectChannelHandler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->sendDirectToChannel(DirectChannelHandler::CHANNEL, 'test');

        $this->assertTrue($handler->wasCalled);
    }

    public function test_direct_channel_throws_when_sending_to_a_channel_with_no_subscribed_handler(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [],
            [],
            ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                SimpleMessageChannelBuilder::createDirectMessageChannel('unhandledChannel'),
            ])
                ->withEnabledModulePackageNames([]),
        );

        $this->expectException(MessageDispatchingException::class);

        $ecotone->sendDirectToChannel('unhandledChannel', 'some');
    }

    public function test_publish_subscribe_channel_dispatches_to_every_subscribed_handler(): void
    {
        $handler = new PublishSubscribeChannelHandlers();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [PublishSubscribeChannelHandlers::class],
            [$handler],
            ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                SimpleMessageChannelBuilder::createPublishSubscribeChannel(PublishSubscribeChannelHandlers::CHANNEL),
            ])
                ->withEnabledModulePackageNames([]),
        );

        $ecotone->sendDirectToChannel(PublishSubscribeChannelHandlers::CHANNEL, 'test');

        $this->assertTrue($handler->firstWasCalled);
        $this->assertTrue($handler->secondWasCalled);
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class DirectChannelHandler
{
    public const CHANNEL = 'directChannel.channel';

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
final class PublishSubscribeChannelHandlers
{
    public const CHANNEL = 'publishSubscribeChannel.channel';

    public bool $firstWasCalled = false;
    public bool $secondWasCalled = false;

    #[InternalHandler(self::CHANNEL, endpointId: 'firstSubscriber')]
    public function handleFirst(string $payload): void
    {
        $this->firstWasCalled = true;
    }

    #[InternalHandler(self::CHANNEL, endpointId: 'secondSubscriber')]
    public function handleSecond(string $payload): void
    {
        $this->secondWasCalled = true;
    }
}
