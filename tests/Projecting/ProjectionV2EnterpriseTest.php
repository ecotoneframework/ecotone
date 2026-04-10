<?php

declare(strict_types=1);

namespace Test\Ecotone\Projecting;

use Ecotone\EventSourcing\Attribute\FromStream;
use Ecotone\EventSourcing\Attribute\ProjectionDelete;
use Ecotone\EventSourcing\Attribute\ProjectionInitialization;
use Ecotone\EventSourcing\Attribute\ProjectionReset;
use Ecotone\EventSourcing\Attribute\ProjectionState;
use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\MethodInvocationException;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Event;
use Ecotone\Projecting\Attribute;
use Ecotone\Projecting\Attribute\Partitioned;
use Ecotone\Projecting\Attribute\Polling;
use Ecotone\Projecting\Attribute\ProjectionBackfill;
use Ecotone\Projecting\Attribute\ProjectionDeployment;
use Ecotone\Projecting\Attribute\ProjectionExecution;
use Ecotone\Projecting\Attribute\ProjectionFlush;
use Ecotone\Projecting\Attribute\ProjectionName;
use Ecotone\Projecting\Attribute\ProjectionRebuild;
use Ecotone\Projecting\Attribute\ProjectionV2;
use Ecotone\Projecting\Attribute\Streaming;
use Ecotone\Projecting\NoOpTransaction;
use Ecotone\Projecting\PartitionProvider;
use Ecotone\Projecting\ProjectionPartitionState;
use Ecotone\Projecting\ProjectionStateStorage;
use Ecotone\Projecting\StreamFilter;
use Ecotone\Projecting\StreamPage;
use Ecotone\Projecting\StreamSource;
use Ecotone\Projecting\Transaction;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ProjectionV2EnterpriseTest extends TestCase
{
    public function test_global_sync_projection_works_without_licence(): void
    {
        $projection = new #[ProjectionV2('test'), FromStream('test_stream')] class {
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
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        $ecotone->withEvents([Event::createWithType('test-event', ['name' => 'Test'])]);
        $ecotone->publishEventWithRoutingKey('trigger', []);

        $this->assertCount(1, $projection->handledEvents);
    }

    public function test_global_async_projection_works_without_licence(): void
    {
        $projection = new #[ProjectionV2('test'), FromStream('test_stream'), Asynchronous('async')] class {
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
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('async'))
        );

        $this->assertNotNull($ecotone);
    }

    public function test_sync_backfill_works_without_licence(): void
    {
        $projection = new #[ProjectionV2('test'), FromStream('test_stream'), ProjectionBackfill] class {
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
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        $this->assertNotNull($ecotone);
    }

    public function test_lifecycle_hooks_work_without_licence(): void
    {
        $projection = new #[ProjectionV2('test'), FromStream('test_stream')] class {
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
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        $this->assertNotNull($ecotone);
    }

    public function test_projection_execution_batch_size_works_without_licence(): void
    {
        $projection = new #[ProjectionV2('test'), FromStream('test_stream'), ProjectionExecution(eventLoadingBatchSize: 50)] class {
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
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        $this->assertNotNull($ecotone);
    }

    public function test_partitioned_projection_requires_licence(): void
    {
        $projection = new #[ProjectionV2('test'), FromStream('test_stream'), Partitioned] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );
    }

    public function test_streaming_projection_requires_licence(): void
    {
        $projection = new #[ProjectionV2('test'), Streaming('streaming_channel')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
        );
    }

    public function test_polling_projection_requires_licence(): void
    {
        $projection = new #[ProjectionV2('test'), FromStream('test_stream'), Polling('test_endpoint')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );
    }

    public function test_async_backfill_requires_licence(): void
    {
        $projection = new #[ProjectionV2('test'), FromStream('test_stream'), ProjectionBackfill(asyncChannelName: 'backfill_channel')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );
    }

    public function test_rebuild_requires_licence(): void
    {
        $projection = new #[ProjectionV2('test'), FromStream('test_stream'), ProjectionRebuild] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );
    }

    public function test_deployment_requires_licence(): void
    {
        $projection = new #[ProjectionV2('test'), FromStream('test_stream'), ProjectionDeployment(manualKickOff: true)] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );
    }

    public function test_custom_stream_source_requires_licence(): void
    {
        $projection = new #[ProjectionV2('test'), FromStream('test_stream')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $customStreamSource = new #[Attribute\StreamSource] class () implements StreamSource {
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
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );
    }

    public function test_custom_state_storage_requires_licence(): void
    {
        $projection = new #[ProjectionV2('test'), FromStream('test_stream')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $customStateStorage = new #[Attribute\StateStorage] class () implements ProjectionStateStorage {
            public function canHandle(string $projectionName): bool
            {
                return true;
            }

            public function loadPartition(string $projectionName, ?string $partitionKey = null, bool $lock = true): ?ProjectionPartitionState
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
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );
    }

    public function test_custom_partition_provider_requires_licence(): void
    {
        $projection = new #[ProjectionV2('test'), FromStream('test_stream')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $customPartitionProvider = new #[Attribute\PartitionProvider] class () implements PartitionProvider {
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
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );
    }

    public function test_projection_name_header_is_not_available_without_licence(): void
    {
        $projection = new #[ProjectionV2('test'), FromStream('test_stream')] class {
            public array $handledEvents = [];

            #[EventHandler('*')]
            public function handle(array $event, #[ProjectionName] string $projectionName): void
            {
                $this->handledEvents[] = ['event' => $event, 'projectionName' => $projectionName];
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        $ecotone->withEvents([Event::createWithType('test-event', ['name' => 'Test'], [MessageHeaders::EVENT_AGGREGATE_ID => '1'])]);

        try {
            $ecotone->publishEventWithRoutingKey('trigger', []);
            self::fail('Should have thrown exception');
        } catch (MethodInvocationException $e) {
            self::assertStringContainsString('projection.name', $e->getMessage(), 'Exception should mention missing projection.name header. Got: ' . $e->getMessage());
        }
    }

    public function test_flush_with_projection_state_requires_licence(): void
    {
        $projection = new #[ProjectionV2('test'), FromStream('test_stream')] class {
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
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );
    }

}
