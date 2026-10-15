<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config;

use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\PublishSubscribeChannel;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ImplicitChannelCreationTest extends TestCase
{
    public function test_creating_an_implicit_direct_channel_for_a_handler_with_no_registered_channel(): void
    {
        $handler = new ImplicitChannelHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([ImplicitChannelHandler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->sendDirectToChannel(ImplicitChannelHandler::CHANNEL, 'some');

        $this->assertTrue($handler->wasCalled);
    }

    public function test_registering_a_default_channel_configuration_makes_it_a_real_channel_instance(): void
    {
        $handler = new ImplicitChannelHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ImplicitChannelHandler::class],
            [$handler],
            ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                SimpleMessageChannelBuilder::createPublishSubscribeChannel(ImplicitChannelHandler::CHANNEL),
            ])
                ->withEnabledModulePackageNames([]),
        );

        $this->assertInstanceOf(PublishSubscribeChannel::class, $ecotone->getMessageChannel(ImplicitChannelHandler::CHANNEL));
    }

    public function test_explicitly_registered_queue_channel_replaces_the_implicit_direct_channel(): void
    {
        $handler = new ImplicitChannelHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ImplicitChannelHandler::class],
            [$handler],
            ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                SimpleMessageChannelBuilder::createQueueChannel(ImplicitChannelHandler::CHANNEL),
            ])
                ->withEnabledModulePackageNames([]),
        );

        $ecotone->sendDirectToChannel(ImplicitChannelHandler::CHANNEL, 'some');

        $this->assertFalse($handler->wasCalled, 'Queue channel was registered, so without explicitly running the consumer it should not be called');
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ImplicitChannelHandler
{
    public const CHANNEL = 'implicitChannel.channel';

    public bool $wasCalled = false;

    #[InternalHandler(self::CHANNEL)]
    public function handle(string $payload): void
    {
        $this->wasCalled = true;
    }
}
