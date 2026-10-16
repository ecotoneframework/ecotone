<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\AsynchronousEndpointIdRequirement;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class AsynchronousEndpointIdRequirementTest extends TestCase
{
    public function test_command_handler_without_endpoint_id_throws(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches('/GeneratedIdCommandHandler::handle::.*CommandHandler should have endpointId defined for handling asynchronously/');

        EcotoneLite::bootstrapFlowTesting(
            [GeneratedIdCommandHandler::class],
            [new GeneratedIdCommandHandler()],
            enabledModules: [],
        );
    }

    public function test_event_handler_without_endpoint_id_throws(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches('/GeneratedIdEventHandler::handle::.*EventHandler should have endpointId defined for handling asynchronously/');

        EcotoneLite::bootstrapFlowTesting(
            [GeneratedIdEventHandler::class],
            [new GeneratedIdEventHandler()],
            enabledModules: [],
        );
    }

    public function test_internal_handler_without_endpoint_id_throws_for_method_level_asynchronous(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches('/GeneratedIdInternalHandlerMethodLevel::handle::.*InternalHandler should have endpointId defined for handling asynchronously/');

        EcotoneLite::bootstrapFlowTesting(
            [GeneratedIdInternalHandlerMethodLevel::class],
            [new GeneratedIdInternalHandlerMethodLevel()],
            enabledModules: [],
        );
    }

    public function test_internal_handler_without_endpoint_id_throws_for_class_level_asynchronous(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches('/GeneratedIdInternalHandlerClassLevel::handle::.*InternalHandler should have endpointId defined for handling asynchronously/');

        EcotoneLite::bootstrapFlowTesting(
            [GeneratedIdInternalHandlerClassLevel::class],
            [new GeneratedIdInternalHandlerClassLevel()],
            enabledModules: [],
        );
    }

    public function test_query_handler_without_endpoint_id_is_exempt(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [GeneratedIdQueryHandler::class],
            [new GeneratedIdQueryHandler()],
            enabledModules: [],
        );

        $this->assertNotNull($ecotone);
    }

    public function test_command_handler_with_explicit_endpoint_id_bootstraps_and_runs(): void
    {
        $handler = new ExplicitIdCommandHandler();
        $ecotone = $this->bootstrap([ExplicitIdCommandHandler::class], [$handler]);

        $ecotone->sendCommandWithRouting('explicit.command', 'payload');
        $ecotone->run(ExplicitIdCommandHandler::CHANNEL, ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertSame('payload', $handler->received);
    }

    public function test_event_handler_with_explicit_endpoint_id_bootstraps_and_runs(): void
    {
        $handler = new ExplicitIdEventHandler();
        $ecotone = $this->bootstrap([ExplicitIdEventHandler::class], [$handler]);

        $ecotone->publishEvent(new SomeEvent('payload'));
        $ecotone->run(ExplicitIdEventHandler::CHANNEL, ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertSame('payload', $handler->received);
    }

    public function test_internal_handler_with_explicit_endpoint_id_bootstraps_and_runs(): void
    {
        $handler = new ExplicitIdInternalHandler();
        $ecotone = $this->bootstrap([ExplicitIdInternalHandler::class], [$handler]);

        $ecotone->sendDirectToChannel(ExplicitIdInternalHandler::INPUT_CHANNEL, 'payload');
        $ecotone->run(ExplicitIdInternalHandler::CHANNEL, ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertSame('payload', $handler->received);
    }

    private function bootstrap(array $classesToResolve, array $services)
    {
        return EcotoneLite::bootstrapFlowTesting(
            $classesToResolve,
            $services,
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel(ExplicitIdCommandHandler::CHANNEL),
                SimpleMessageChannelBuilder::createQueueChannel(ExplicitIdEventHandler::CHANNEL),
                SimpleMessageChannelBuilder::createQueueChannel(ExplicitIdInternalHandler::CHANNEL),
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
final class GeneratedIdCommandHandler
{
    #[Asynchronous('generatedId.command.channel')]
    #[CommandHandler('generatedId.command')]
    public function handle(string $payload): void
    {
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class GeneratedIdEventHandler
{
    #[Asynchronous('generatedId.event.channel')]
    #[EventHandler]
    public function handle(GeneratedIdSomeEvent $event): void
    {
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class GeneratedIdSomeEvent
{
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class GeneratedIdInternalHandlerMethodLevel
{
    #[Asynchronous('generatedId.internal.method.channel')]
    #[InternalHandler('generatedId.internal.method.input')]
    public function handle(string $payload): void
    {
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
#[Asynchronous('generatedId.internal.class.channel')]
final class GeneratedIdInternalHandlerClassLevel
{
    #[InternalHandler('generatedId.internal.class.input')]
    public function handle(string $payload): void
    {
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class GeneratedIdQueryHandler
{
    #[Asynchronous('generatedId.query.channel')]
    #[QueryHandler('generatedId.query')]
    public function handle(): string
    {
        return 'result';
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ExplicitIdCommandHandler
{
    public const CHANNEL = 'explicitId.command.channel';

    public ?string $received = null;

    #[Asynchronous(self::CHANNEL)]
    #[CommandHandler('explicit.command', 'explicitIdCommandHandler')]
    public function handle(string $payload): void
    {
        $this->received = $payload;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class SomeEvent
{
    public function __construct(public string $payload)
    {
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ExplicitIdEventHandler
{
    public const CHANNEL = 'explicitId.event.channel';

    public ?string $received = null;

    #[Asynchronous(self::CHANNEL)]
    #[EventHandler(endpointId: 'explicitIdEventHandler')]
    public function handle(SomeEvent $event): void
    {
        $this->received = $event->payload;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ExplicitIdInternalHandler
{
    public const CHANNEL = 'explicitId.internal.channel';
    public const INPUT_CHANNEL = 'explicitId.internal.input';

    public ?string $received = null;

    #[Asynchronous(self::CHANNEL)]
    #[InternalHandler(self::INPUT_CHANNEL, endpointId: 'explicitIdInternalHandler')]
    public function handle(string $payload): void
    {
        $this->received = $payload;
    }
}
