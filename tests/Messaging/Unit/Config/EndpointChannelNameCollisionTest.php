<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config;

use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class EndpointChannelNameCollisionTest extends TestCase
{
    public function test_throwing_exception_when_an_endpoint_id_collides_with_an_explicitly_registered_channel_name(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            [CollidingEndpointIdHandler::class],
            [new CollidingEndpointIdHandler()],
            ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                SimpleMessageChannelBuilder::createDirectMessageChannel(CollidingEndpointIdHandler::ENDPOINT_ID),
            ])
                ->withEnabledModulePackageNames([]),
        );
    }

    public function test_throwing_exception_when_a_handler_has_the_same_input_channel_name_and_endpoint_id(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting([SameChannelAndEndpointIdHandler::class], [new SameChannelAndEndpointIdHandler()], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class CollidingEndpointIdHandler
{
    public const ENDPOINT_ID = 'order.register';

    #[InternalHandler('some', endpointId: self::ENDPOINT_ID)]
    public function handle(string $payload): void
    {
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class SameChannelAndEndpointIdHandler
{
    public const CHANNEL = 'order.register';

    #[InternalHandler(self::CHANNEL, endpointId: self::CHANNEL)]
    public function handle(string $payload): void
    {
    }
}
