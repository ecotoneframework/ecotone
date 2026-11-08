<?php

declare(strict_types=1);

namespace Test\Ecotone\Projecting;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\ExtensionObject\TestConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Projecting\FromStream;
use Ecotone\Api\Projecting\Partitioned;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Projecting\ProjectionInitialization;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class ProjectionInitializationTest extends TestCase
{
    public function test_an_event_reaching_a_projection_that_was_never_initialized_is_refused_naming_the_command_that_initializes_it(): void
    {
        $projection = $this->globalProjection();
        $ecotone = $this->bootstrap($projection, initializedOnBootstrap: false);

        $this->assertRefusedAsNeverInitialized('initialization_projection', fn () => $this->recordTicket($ecotone, 't-1'));
        self::assertSame(0, $projection->initializations);
        self::assertSame([], $projection->projected);
    }

    public function test_an_event_reaching_an_asynchronous_projection_that_was_never_initialized_is_refused(): void
    {
        $projection = new #[Projection('async_initialization_projection'), FromStream('tickets'), Asynchronous('projections')] class () {
            public int $initializations = 0;

            #[EventHandler('ticket.created')]
            public function on(array $event): void
            {
            }

            #[ProjectionInitialization]
            public function initialize(): void
            {
                $this->initializations++;
            }
        };
        $ecotone = $this->bootstrap($projection, initializedOnBootstrap: false, extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('projections')]);
        $this->recordTicket($ecotone, 't-1');

        $this->assertRefusedAsNeverInitialized('async_initialization_projection', fn () => $ecotone->run('projections', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1)));
        self::assertSame(0, $projection->initializations);
    }

    public function test_an_event_reaching_a_partitioned_projection_that_was_never_initialized_is_refused(): void
    {
        $projection = $this->partitionedProjection();
        $ecotone = $this->bootstrap($projection, initializedOnBootstrap: false);

        $this->assertRefusedAsNeverInitialized('partitioned_initialization_projection', fn () => $this->recordTicket($ecotone, 't-1', 'tenant-1'));
        self::assertSame(0, $projection->initializations);
    }

    public function test_triggering_a_projection_that_was_never_initialized_is_refused(): void
    {
        $projection = $this->globalProjection();
        $ecotone = $this->bootstrap($projection, initializedOnBootstrap: false);

        $this->assertRefusedAsNeverInitialized('initialization_projection', fn () => $ecotone->triggerProjection('initialization_projection'));
        self::assertSame(0, $projection->initializations);
    }

    public function test_a_flow_test_initializes_its_projections_on_bootstrap(): void
    {
        $projection = $this->globalProjection();
        $ecotone = $this->bootstrap($projection, initializedOnBootstrap: true);

        self::assertSame(1, $projection->initializations);

        $this->recordTicket($ecotone, 't-1');

        self::assertSame(1, $projection->initializations);
        self::assertSame(['t-1'], $projection->projected);
    }

    public function test_a_new_partition_of_an_initialized_projection_does_not_run_its_initialization_again(): void
    {
        $projection = $this->partitionedProjection();
        $ecotone = $this->bootstrap($projection, initializedOnBootstrap: true);

        $this->recordTicket($ecotone, 't-1', 'tenant-1');
        $this->recordTicket($ecotone, 't-2', 'tenant-2');

        self::assertSame(1, $projection->initializations);
        self::assertContains('t-2', $projection->projected);
    }

    public function test_initializing_an_initialized_projection_again_leaves_it_as_it_is_until_it_is_deleted(): void
    {
        $projection = $this->globalProjection();
        $ecotone = $this->bootstrap($projection, initializedOnBootstrap: true);

        $ecotone->initializeProjection('initialization_projection');
        self::assertSame(1, $projection->initializations);

        $ecotone->deleteProjection('initialization_projection')->initializeProjection('initialization_projection');
        self::assertSame(2, $projection->initializations);
    }

    private function recordTicket(FlowTestSupport $ecotone, string $ticketId, ?string $tenantId = null): void
    {
        $metadata = [MessageHeaders::EVENT_AGGREGATE_ID => $ticketId, ...($tenantId === null ? [] : ['tenantId' => $tenantId])];
        $ecotone->withEvents([Event::createWithType('ticket.created', ['ticketId' => $ticketId], $metadata)]);
        $ecotone->publishEventWithRouting('ticket.created', ['ticketId' => $ticketId], metadata: $metadata);
    }

    private function assertRefusedAsNeverInitialized(string $projectionName, callable $action): void
    {
        try {
            $action();
            self::fail('Expected the never initialized projection to be refused');
        } catch (ConfigurationException $exception) {
            self::assertStringContainsString("Projection '{$projectionName}' has not been initialized", $exception->getMessage());
            self::assertStringContainsString("ecotone:projection:init {$projectionName}", $exception->getMessage());
            self::assertStringContainsString("\$ecotone->initializeProjection('{$projectionName}')", $exception->getMessage());
        }
    }

    private function globalProjection(): object
    {
        return new #[Projection('initialization_projection'), FromStream('tickets')] class () {
            public int $initializations = 0;
            public array $projected = [];

            #[EventHandler('ticket.created')]
            public function on(array $event): void
            {
                $this->projected[] = $event['ticketId'];
            }

            #[ProjectionInitialization]
            public function initialize(): void
            {
                $this->initializations++;
            }
        };
    }

    private function partitionedProjection(): object
    {
        return new #[Projection('partitioned_initialization_projection'), FromStream('tickets'), Partitioned('tenantId')] class () {
            public int $initializations = 0;
            public array $projected = [];

            #[EventHandler('ticket.created')]
            public function on(array $event): void
            {
                $this->projected[] = $event['ticketId'];
            }

            #[ProjectionInitialization]
            public function initialize(): void
            {
                $this->initializations++;
            }
        };
    }

    private function bootstrap(object $projection, bool $initializedOnBootstrap, array $extensionObjects = []): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            extensionObjects: $extensionObjects,
            enabledModules: [],
            testConfiguration: TestConfiguration::createWithDefaults()->withDatabaseTablesSetUpOnBootstrap($initializedOnBootstrap),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}
