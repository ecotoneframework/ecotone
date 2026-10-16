<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config;

use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class HandlerAndChannelNamingValidationTest extends TestCase
{
    public function test_throwing_exception_when_two_handlers_are_registered_with_the_same_endpoint_id(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            [DuplicateEndpointIdHandlers::class],
            [new DuplicateEndpointIdHandlers()],
            enabledModules: [],
        );
    }

    public function test_handlers_with_no_explicit_endpoint_id_are_registered_with_generated_ids(): void
    {
        $handler = new GeneratedEndpointIdHandlers();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [GeneratedEndpointIdHandlers::class],
            [$handler],
            enabledModules: [],
        );

        $ecotone->sendDirectToChannel('channelOne', 'a');
        $ecotone->sendDirectToChannel('channelTwo', 'b');

        $this->assertSame(['a', 'b'], $handler->received);
    }

    public function test_throwing_exception_when_two_channels_are_registered_with_the_same_name(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            [],
            [],
            extensionObjects: [
                SimpleMessageChannelBuilder::createDirectMessageChannel('duplicateChannel'),
                SimpleMessageChannelBuilder::createQueueChannel('duplicateChannel'),
            ],
            enabledModules: [],
        );
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class DuplicateEndpointIdHandlers
{
    #[InternalHandler('channelOne', endpointId: 'duplicate')]
    public function handleOne(string $payload): void
    {
    }

    #[InternalHandler('channelTwo', endpointId: 'duplicate')]
    public function handleTwo(string $payload): void
    {
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class GeneratedEndpointIdHandlers
{
    /** @var string[] */
    public array $received = [];

    #[InternalHandler('channelOne')]
    public function handleOne(string $payload): void
    {
        $this->received[] = $payload;
    }

    #[InternalHandler('channelTwo')]
    public function handleTwo(string $payload): void
    {
        $this->received[] = $payload;
    }
}
