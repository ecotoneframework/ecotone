<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class EndpointErrorChannelTest extends TestCase
{
    public function test_failed_message_falls_back_to_the_globally_configured_default_error_channel(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ThrowingHandler::class],
            [new ThrowingHandler()],
            ServiceConfiguration::createWithDefaults()
                ->withDefaultErrorChannel('appErrorChannel')
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel(ThrowingHandler::CHANNEL),
                    SimpleMessageChannelBuilder::createQueueChannel('appErrorChannel'),
                ])
                    ->withEnabledModulePackageNames([]),
        );

        $ecotone->sendCommandWithRouting(ThrowingHandler::ROUTING_KEY, 'some');
        $ecotone->run(ThrowingHandler::CHANNEL, ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false));

        $this->assertNotNull($ecotone->receiveMessageFrom('appErrorChannel'));
    }

    public function test_disabling_the_error_channel_lets_the_exception_propagate(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ThrowingHandler::class],
            [new ThrowingHandler()],
            ServiceConfiguration::createWithDefaults()
                ->withDefaultErrorChannel('appErrorChannel')
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel(ThrowingHandler::CHANNEL),
                    SimpleMessageChannelBuilder::createQueueChannel('appErrorChannel'),
                    PollingMetadata::create(ThrowingHandler::ENDPOINT_ID)->withEnabledErrorChannel(false),
                ])
                    ->withEnabledModulePackageNames([]),
        );

        $ecotone->sendCommandWithRouting(ThrowingHandler::ROUTING_KEY, 'some');

        $this->expectException(InvalidArgumentException::class);

        $ecotone->run(ThrowingHandler::CHANNEL, ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: true));
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ThrowingHandler
{
    public const CHANNEL = 'errorChannel.throwing';
    public const ROUTING_KEY = 'errorChannel.handle';
    public const ENDPOINT_ID = 'throwingEndpoint';

    #[Asynchronous(self::CHANNEL)]
    #[CommandHandler(self::ROUTING_KEY, self::ENDPOINT_ID)]
    public function handle(string $payload): void
    {
        throw new InvalidArgumentException('boom');
    }
}
