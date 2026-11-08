<?php

declare(strict_types=1);

namespace Test\Ecotone\Projecting;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Polling;
use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Projecting\FromStream;
use Ecotone\Api\Projecting\Partitioned;
use Ecotone\Api\Projecting\PartitionProvider as PartitionProviderAttribute;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Projecting\ProjectionBackfill;
use Ecotone\Api\Projecting\ProjectionDelete;
use Ecotone\Api\Projecting\ProjectionDeployment;
use Ecotone\Api\Projecting\ProjectionExecution;
use Ecotone\Api\Projecting\ProjectionFlush;
use Ecotone\Api\Projecting\ProjectionInitialization;
use Ecotone\Api\Projecting\ProjectionName;
use Ecotone\Api\Projecting\ProjectionRebuild;
use Ecotone\Api\Projecting\ProjectionReset;
use Ecotone\Api\Projecting\ProjectionState;
use Ecotone\Api\Projecting\StateStorage;
use Ecotone\Api\Projecting\Streaming;
use Ecotone\Api\Projecting\StreamSource as StreamSourceAttribute;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Projecting\NoOpTransaction;
use Ecotone\Projecting\PartitionProvider;
use Ecotone\Projecting\ProjectionPartitionState;
use Ecotone\Projecting\ProjectionStateStorage;
use Ecotone\Projecting\StreamFilter;
use Ecotone\Projecting\StreamPage;
use Ecotone\Projecting\StreamSource;
use Ecotone\Projecting\Transaction;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ProjectionEnterpriseTest extends TestCase
{
    public function test_global_sync_projection_works_without_licence(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream')] class {
            public array $handledEvents = [];

            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->handledEvents[] = $event;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
        );

        $ecotone->withEvents([Event::createWithType('test-event', ['name' => 'Test'])]);
        $ecotone->publishEventWithRouting('trigger', []);

        $this->assertCount(1, $projection->handledEvents);
    }

    public function test_global_async_projection_works_without_licence(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream'), Asynchronous('async')] class {
            public array $handledEvents = [];

            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->handledEvents[] = $event;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('async')],
            enabledModules: [],
        );

        $this->assertNotNull($ecotone);
    }

    public function test_sync_backfill_works_without_licence(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream'), ProjectionBackfill] class {
            public array $handledEvents = [];

            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->handledEvents[] = $event;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
        );

        $this->assertNotNull($ecotone);
    }

    public function test_lifecycle_hooks_work_without_licence(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream')] class {
            public bool $initialized = false;
            public bool $deleted = false;
            public bool $reset = false;
            public bool $flushed = false;
            public array $handledEvents = [];

            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->handledEvents[] = $event;
            }

            #[ProjectionInitialization]
            public function init(): void
            {
                $this->initialized = true;
            }

            #[ProjectionDelete]
            public function remove(): void
            {
                $this->deleted = true;
            }

            #[ProjectionReset]
            public function resetState(): void
            {
                $this->reset = true;
            }

            #[ProjectionFlush]
            public function flush(): void
            {
                $this->flushed = true;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
        );

        $this->assertNotNull($ecotone);
    }

    public function test_projection_execution_batch_size_works_without_licence(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream'), ProjectionExecution(eventLoadingBatchSize: 50)] class {
            public array $handledEvents = [];

            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->handledEvents[] = $event;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
        );

        $this->assertNotNull($ecotone);
    }

    public function test_partitioned_projection_requires_licence(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream'), Partitioned] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
        );
    }

    public function test_streaming_projection_requires_licence(): void
    {
        $projection = new #[Projection('test'), Streaming('streaming_channel')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
        );
    }

    public function test_polling_projection_requires_licence(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream'), Polling('test_endpoint')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
        );
    }

    public function test_async_backfill_requires_licence(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream'), ProjectionBackfill(asyncChannelName: 'backfill_channel')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
        );
    }

    public function test_rebuild_requires_licence(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream'), ProjectionRebuild] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
        );
    }

    public function test_deployment_requires_licence(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream'), ProjectionDeployment(manualKickOff: true)] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
        );
    }

    public function test_custom_stream_source_requires_licence(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $customStreamSource = new #[StreamSourceAttribute] class () implements StreamSource {
            public function canHandle(string $projectionName): bool
            {
                return true;
            }

            public function load(string $projectionName, ?string $lastPosition, int $count, ?string $partitionKey = null): StreamPage
            {
                return new StreamPage([], '0');
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class, $customStreamSource::class],
            [$projection, $customStreamSource],
            enabledModules: [],
        );
    }

    public function test_custom_state_storage_requires_licence(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $customStateStorage = new #[StateStorage] class () implements ProjectionStateStorage {
            public function canHandle(string $projectionName): bool
            {
                return true;
            }

            public function loadPartition(string $projectionName, ?string $partitionKey = null): ?ProjectionPartitionState
            {
                return null;
            }

            public function initPartition(string $projectionName, ?string $partitionKey = null): ?ProjectionPartitionState
            {
                return null;
            }

            public function savePartition(ProjectionPartitionState $projectionState): void
            {
            }

            public function delete(string $projectionName): void
            {
            }

            public function init(string $projectionName): void
            {
            }

            public function beginTransaction(): Transaction
            {
                return new NoOpTransaction();
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class, $customStateStorage::class],
            [$projection, $customStateStorage],
            enabledModules: [],
        );
    }

    public function test_custom_partition_provider_requires_licence(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $customPartitionProvider = new #[PartitionProviderAttribute] class () implements PartitionProvider {
            public function canHandle(string $projectionName): bool
            {
                return true;
            }

            public function count(StreamFilter $filter): int
            {
                return 0;
            }

            public function partitions(StreamFilter $filter, ?int $limit = null, int $offset = 0): iterable
            {
                return [];
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class, $customPartitionProvider::class],
            [$projection, $customPartitionProvider],
            enabledModules: [],
        );
    }

    public function test_projection_name_on_an_event_handler_requires_licence(): void
    {
        $projection = new #[Projection('named_projection'), FromStream('test_stream')] class {
            #[EventHandler('*')]
            public function handle(array $event, #[ProjectionName] string $projectionName): void
            {
            }
        };

        $this->expectException(LicensingException::class);
        $this->expectExceptionMessageMatches('/^' . preg_quote($this->projectionNameRefusal($projection::class . '::handle', 'named_projection'), '/') . '$/');

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
        );
    }

    public function test_projection_name_on_a_lifecycle_handler_requires_licence(): void
    {
        $projections = [
            'init' => new #[Projection('named_projection'), FromStream('test_stream')] class {
                #[EventHandler('*')]
                public function handle(array $event): void
                {
                }

                #[ProjectionInitialization]
                public function init(#[ProjectionName] string $projectionName): void
                {
                }
            },
            'delete' => new #[Projection('named_projection'), FromStream('test_stream')] class {
                #[EventHandler('*')]
                public function handle(array $event): void
                {
                }

                #[ProjectionDelete]
                public function delete(#[ProjectionName] string $projectionName): void
                {
                }
            },
            'flush' => new #[Projection('named_projection'), FromStream('test_stream')] class {
                #[EventHandler('*')]
                public function handle(array $event): void
                {
                }

                #[ProjectionFlush]
                public function flush(#[ProjectionName] string $projectionName): void
                {
                }
            },
            'reset' => new #[Projection('named_projection'), FromStream('test_stream')] class {
                #[EventHandler('*')]
                public function handle(array $event): void
                {
                }

                #[ProjectionReset]
                public function reset(#[ProjectionName] string $projectionName): void
                {
                }
            },
        ];

        $refusals = [];
        foreach ($projections as $methodName => $projection) {
            try {
                EcotoneLite::bootstrapFlowTesting(
                    [$projection::class],
                    [$projection],
                    enabledModules: [],
                );
                $refusals[$methodName] = '';
            } catch (LicensingException $exception) {
                $refusals[$methodName] = $exception->getMessage();
            }
        }

        foreach ($projections as $methodName => $projection) {
            self::assertSame(
                $this->projectionNameRefusal($projection::class . '::' . $methodName, 'named_projection'),
                $refusals[$methodName]
            );
        }
    }

    public function test_projection_name_is_passed_to_a_licensed_projection(): void
    {
        $projection = new #[Projection('named_projection'), FromStream('test_stream')] class {
            public array $projectionNames = [];

            #[EventHandler('*')]
            public function handle(array $event, #[ProjectionName] string $projectionName): void
            {
                $this->projectionNames[] = $projectionName;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->withEvents([Event::createWithType('test-event', ['name' => 'Test'], [MessageHeaders::EVENT_AGGREGATE_ID => '1'])]);
        $ecotone->publishEventWithRouting('trigger', []);

        self::assertSame(['named_projection'], $projection->projectionNames);
    }

    public function test_flush_with_projection_state_requires_licence(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream')] class {
            #[EventHandler('*')]
            public function handle(array $event): array
            {
                return $event;
            }

            #[ProjectionFlush]
            public function flush(#[ProjectionState] array $state = []): void
            {
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
        );
    }

    private function projectionNameRefusal(string $handler, string $projectionName): string
    {
        return "#[ProjectionName] on \$projectionName of {$handler} requires the projection name, which is passed to a projection handler only under an Ecotone Enterprise licence, and this application runs without one: a parameter that is neither nullable nor defaulted would fail on every call. Either declare it as ?string \$projectionName = null to receive null without a licence, remove #[ProjectionName] if the handler does not need the name (the projection declares it in #[Projection('{$projectionName}')]), or obtain an Enterprise licence (see https://docs.ecotone.tech/enterprise) and pass it as the licenceKey of your framework's Ecotone configuration or of EcotoneLite::bootstrap(). To evaluate Enterprise features, start a trial at https://ecotone.tech/pricing or email us at \"support@simplycodedsoftware.com\" for a trial key. Production licenses are available at https://ecotone.tech. Pass the key with the licenceKey: argument of EcotoneLite::bootstrap() (Ecotone Lite), 'licenceKey' in config/ecotone.php (Laravel), ecotone.licenceKey in your Symfony configuration, or the licenceKey argument of EcotoneConfig (Tempest).";
    }
}
