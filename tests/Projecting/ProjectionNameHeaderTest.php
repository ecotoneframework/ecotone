<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Test\Ecotone\Projecting;

use Closure;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Projecting\FromStream;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Projecting\ProjectionDelete;
use Ecotone\Api\Projecting\ProjectionFlush;
use Ecotone\Api\Projecting\ProjectionInitialization;
use Ecotone\Api\Projecting\ProjectionName;
use Ecotone\Api\Projecting\ProjectionReset;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ProjectionNameHeaderTest extends TestCase
{
    public static function projectionPaths(): iterable
    {
        yield 'event handler' => ['eventHandler', static fn (FlowTestSupport $ecotone) => $ecotone->triggerProjection('name_header_projection')];
        yield 'flush' => ['flush', static fn (FlowTestSupport $ecotone) => $ecotone->triggerProjection('name_header_projection')];
        yield 'initialization' => ['initialization', static fn (FlowTestSupport $ecotone) => $ecotone->initializeProjection('name_header_projection')];
        yield 'delete' => ['delete', static fn (FlowTestSupport $ecotone) => $ecotone->deleteProjection('name_header_projection')];
        yield 'reset during rebuild' => ['reset', static fn (FlowTestSupport $ecotone) => $ecotone->triggerProjection('name_header_projection')->runConsoleCommand('ecotone:projection:rebuild', ['name' => 'name_header_projection'])];
    }

    #[DataProvider('projectionPaths')]
    public function test_projection_name_is_not_given_to_projection_without_licence(string $path, Closure $drive): void
    {
        $projection = $this->projectionRecordingItsName();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $drive($ecotone->withEvents([Event::createWithType('ticket.registered', ['ticketId' => '1'])]));

        self::assertSame([null], $projection->receivedNames[$path]);
    }

    #[DataProvider('projectionPaths')]
    public function test_projection_name_is_given_to_projection_with_licence(string $path, Closure $drive): void
    {
        $projection = $this->projectionRecordingItsName();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $drive($ecotone->withEvents([Event::createWithType('ticket.registered', ['ticketId' => '1'])]));

        self::assertSame(['name_header_projection'], $projection->receivedNames[$path]);
    }

    private function projectionRecordingItsName(): object
    {
        return new #[Projection('name_header_projection'), FromStream('name_header_stream')] class {
            public array $receivedNames = ['eventHandler' => [], 'flush' => [], 'initialization' => [], 'delete' => [], 'reset' => []];

            #[EventHandler('ticket.registered')]
            public function handle(array $event, #[ProjectionName] ?string $projectionName = null): void
            {
                $this->receivedNames['eventHandler'][] = $projectionName;
            }

            #[ProjectionFlush]
            public function flush(#[ProjectionName] ?string $projectionName = null): void
            {
                $this->receivedNames['flush'][] = $projectionName;
            }

            #[ProjectionInitialization]
            public function init(#[ProjectionName] ?string $projectionName = null): void
            {
                $this->receivedNames['initialization'][] = $projectionName;
            }

            #[ProjectionDelete]
            public function delete(#[ProjectionName] ?string $projectionName = null): void
            {
                $this->receivedNames['delete'][] = $projectionName;
            }

            #[ProjectionReset]
            public function reset(#[ProjectionName] ?string $projectionName = null): void
            {
                $this->receivedNames['reset'][] = $projectionName;
            }
        };
    }
}
