<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Collecting;

use DateTimeImmutable;
use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\Collected;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Gathered;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Saga;
use Ecotone\Api\Attribute\Splitter;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\CollectedMessages;
use Ecotone\Api\Messaging\GatheredParts;
use Ecotone\Api\Scheduling\TimeSpan;
use Ecotone\Api\Sqs\SqsBackedMessageChannelBuilder;
use Ecotone\Api\Sqs\SqsConnectionFactory;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class CollectingTest extends TestCase
{
    public function test_collected_handler_without_asynchronous_is_refused_at_bootstrap(): void
    {
        $handler = new class () {
            #[Collected(maxSize: 2)]
            #[InternalHandler('collect', endpointId: 'collect.handler')]
            public function collect(mixed $batch): void
            {
            }
        };

        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') requires an asynchronous channel. Add #[Asynchronous('channelName')] to the handler, or remove #[Collected].");

        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            enabledModules: [],
        );
    }

    public function test_collected_handler_without_max_size_or_max_wait_is_refused_at_bootstrap(): void
    {
        $handler = new class () {
            #[Collected]
            #[Asynchronous('batches')]
            #[InternalHandler('collect', endpointId: 'collect.handler')]
            public function collect(mixed $batch): void
            {
            }
        };

        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') needs a release limit. Set maxSize or maxWait on #[Collected], or remove #[Collected].");

        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
        );
    }

    public function test_collected_handler_requires_an_enterprise_licence(): void
    {
        $handler = new class () {
            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            #[InternalHandler('collect', endpointId: 'collect.handler')]
            public function collect(mixed $batch): void
            {
            }
        };

        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') requires an Ecotone Enterprise licence. Provide your licence key as `licenceKey` in the Symfony, Laravel or Tempest Ecotone configuration, or as the `licenceKey:` argument of EcotoneLite, or start a trial at https://ecotone.tech/pricing. Alternatively, remove #[Collected].");

        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
        );
    }

    public function test_collected_event_handler_without_explicit_subscription_is_refused_at_bootstrap(): void
    {
        $handler = new class () {
            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            #[EventHandler(endpointId: 'collect.handler')]
            public function collect(mixed $batch): void
            {
            }
        };

        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') cannot infer its subscription because the first parameter is CollectedMessages. Set #[EventHandler(listenTo: YourEvent::class, endpointId: 'collect.handler')], or remove #[Collected].");

        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_command_handler_without_explicit_subscription_is_refused_at_bootstrap(): void
    {
        $handler = new class () {
            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            #[CommandHandler(endpointId: 'collect.handler')]
            public function collect(mixed $batch): void
            {
            }
        };

        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') cannot infer its subscription because the first parameter is CollectedMessages. Set #[CommandHandler(routingKey: 'your.routing.key', endpointId: 'collect.handler')], or remove #[Collected].");

        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_query_handler_is_refused_at_bootstrap(): void
    {
        $handler = new class () {
            #[Collected(maxSize: 2)]
            #[QueryHandler('collect')]
            public function collect(mixed $batch): array
            {
                return [];
            }
        };

        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId '') is not an allowed message handler: a query caller waits for an answer. Stack #[Collected] on a service #[CommandHandler], #[EventHandler] or #[InternalHandler] with #[Asynchronous], or remove #[Collected].");

        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_handler_on_an_aggregate_is_refused_at_bootstrap(): void
    {
        $handler = new #[Aggregate] class () {
            #[Identifier]
            public string $id = 'batch';

            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            #[CommandHandler('collect', endpointId: 'collect.handler')]
            public static function collect(mixed $batch): self
            {
                return new self();
            }
        };

        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') belongs to an aggregate or saga, but a batch has no single instance to load. Move #[Collected] to a service #[CommandHandler], #[EventHandler] or #[InternalHandler], or remove #[Collected].");

        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_handler_on_a_saga_is_refused_at_bootstrap(): void
    {
        $handler = new #[Saga] class () {
            #[Identifier]
            public string $id = 'batch';

            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            #[CommandHandler('collect', endpointId: 'collect.handler')]
            public static function collect(mixed $batch): self
            {
                return new self();
            }
        };

        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') belongs to an aggregate or saga, but a batch has no single instance to load. Move #[Collected] to a service #[CommandHandler], #[EventHandler] or #[InternalHandler], or remove #[Collected].");

        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_and_gathered_on_one_handler_are_refused_at_bootstrap(): void
    {
        $handler = new class () {
            #[Collected(maxSize: 2)]
            #[Gathered]
            #[Asynchronous('batches')]
            #[InternalHandler('collect', endpointId: 'collect.handler')]
            public function collect(mixed $batch): void
            {
            }
        };

        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') also has #[Gathered]: collecting ordinary messages and gathering split parts cannot share a handler. Remove #[Gathered] to collect ordinary messages, or remove #[Collected] to gather split parts.");

        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_without_an_ordinary_handler_is_refused_at_bootstrap(): void
    {
        $handler = new class () {
            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            public function collect(mixed $batch): void
            {
            }
        };

        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId '') has no ordinary message handler attribute. Stack it on a service #[CommandHandler], #[EventHandler] or #[InternalHandler], or remove #[Collected].");

        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_handler_runs_once_when_the_batch_reaches_max_size(): void
    {
        $handler = new class () {
            public array $batches = [];

            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            #[CommandHandler('collect', endpointId: 'collect.handler')]
            public function collect(CollectedMessages $batch): void
            {
                $this->batches[] = $batch;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->sendCommandWithRouting('collect', 'first');
        $ecotone->run('batches');
        self::assertSame([], $handler->batches);

        $ecotone->sendCommandWithRouting('collect', 'second');
        $ecotone->run('batches');
        self::assertCount(1, $handler->batches);
        self::assertSame(['first', 'second'], array_column($handler->batches[0]->messages, 'payload'));
        self::assertSame(2, count($handler->batches[0]->messages));
        self::assertNull($handler->batches[0]->groupKey);
    }

    public function test_collected_handler_requires_the_typed_batch_as_its_first_parameter(): void
    {
        $handler = new class () {
            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            #[InternalHandler('collect', endpointId: 'collect.handler')]
            public function collect(array $batch): void
            {
            }
        };
        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') must receive Ecotone\\Api\\Messaging\\CollectedMessages as its first payload parameter. Change that parameter type to CollectedMessages, or remove #[Collected].");
        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_max_wait_requires_a_delayable_asynchronous_channel(): void
    {
        $handler = new class () {
            #[Collected(maxWait: new TimeSpan(seconds: 10))]
            #[Asynchronous('batches')]
            #[InternalHandler('collect', endpointId: 'collect.handler')]
            public function collect(CollectedMessages $batch): void
            {
            }
        };
        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') has maxWait but asynchronous channel 'batches' cannot hold delayed messages. Use a channel builder implementing DelayedMessageChannelBuilder with delay support, enable delays on this channel, or remove maxWait from #[Collected].");
        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches', delayable: false)],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_max_wait_exceeding_the_channels_maximum_names_the_handler_and_ways_out(): void
    {
        if (! class_exists(SqsBackedMessageChannelBuilder::class)) {
            self::markTestSkipped('A bounded-delay public channel builder requires the optional SQS package.');
        }
        $handler = new class () {
            #[Collected(maxWait: new TimeSpan(minutes: 15, milliseconds: 1))]
            #[Asynchronous('batches')]
            #[InternalHandler('collect', endpointId: 'collect.handler')]
            public function collect(CollectedMessages $batch): void
            {
            }
        };
        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') maxWait of 900001 milliseconds exceeds asynchronous channel 'batches' maximum delay of 900000 milliseconds. Reduce maxWait in #[Collected], use a channel with a longer maximum delay, or remove maxWait.");
        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler, SqsConnectionFactory::class => new SqsConnectionFactory(getenv('SQS_DSN'))],
            extensionObjects: [SqsBackedMessageChannelBuilder::create('batches')],
            enabledModules: [ModulePackageList::SQS_PACKAGE],
            licenceKey: LicenceTesting::VALID_LICENCE,
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
        );
    }

    public function test_collected_handler_refuses_zero_max_size_at_bootstrap(): void
    {
        $handler = new class () {
            #[Collected(maxSize: 0)]
            #[Asynchronous('batches')]
            #[InternalHandler('collect', endpointId: 'collect.handler')]
            public function collect(CollectedMessages $batch): void
            {
            }
        };
        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') maxSize is 0; maxSize must be at least 1. Set maxSize to a positive integer, omit maxSize and set maxWait, or remove #[Collected].");
        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_handler_refuses_zero_max_wait_at_bootstrap(): void
    {
        $handler = new class () {
            #[Collected(maxWait: new TimeSpan(milliseconds: 0))]
            #[Asynchronous('batches')]
            #[InternalHandler('collect', endpointId: 'collect.handler')]
            public function collect(CollectedMessages $batch): void
            {
            }
        };
        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') maxWait is 0 milliseconds; maxWait must be greater than 0. Set a positive TimeSpan, omit maxWait and set maxSize, or remove #[Collected].");
        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_internal_handler_without_explicit_subscription_is_refused_at_bootstrap(): void
    {
        $handler = new class () {
            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            #[InternalHandler('', endpointId: 'collect.handler')]
            public function collect(CollectedMessages $batch): void
            {
            }
        };
        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') cannot infer its subscription because the first parameter is CollectedMessages. Set #[InternalHandler(inputChannelName: 'your.input.channel', endpointId: 'collect.handler')], or remove #[Collected].");
        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_handler_requires_durable_storage_outside_flow_testing(): void
    {
        $handler = new class () {
            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            #[InternalHandler('collect', endpointId: 'collect.handler')]
            public function collect(CollectedMessages $batch): void
            {
            }
        };
        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') requires durable storage outside flow testing: batches must survive restarts and be shared between consumers. Install ecotone/dbal and enable its module: add ModulePackageList::DBAL_PACKAGE to EcotoneLite::bootstrap(..., enabledModules: [...]), or 'dbal' to enabledModulePackageNames (the Symfony bundle option, config/ecotone.php key in Laravel, or EcotoneConfig argument in Tempest). Before the first message, run ecotone:migration:database:setup --initialize --feature=gathering, or generate SQL for your migrations with ecotone:migration:database:setup --sql --feature=gathering. Use EcotoneLite::bootstrapFlowTesting() for in-memory flow tests, or remove #[Collected].");
        EcotoneLite::bootstrap(
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_internal_handler_requires_an_explicit_endpoint_id(): void
    {
        $handler = new class () {
            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            #[InternalHandler('collect')]
            public function collect(CollectedMessages $batch): void
            {
            }
        };
        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId '') has no explicit endpointId. Open batches are stored under endpointId, so it must stay stable across deploys. Set #[InternalHandler(inputChannelName: 'collect', endpointId: 'your.stable.id')], or remove #[Collected].");
        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_command_handler_requires_an_explicit_endpoint_id(): void
    {
        $handler = new class () {
            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            #[CommandHandler('collect')]
            public function collect(CollectedMessages $batch): void
            {
            }
        };
        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId '') has no explicit endpointId. Open batches are stored under endpointId, so it must stay stable across deploys. Set #[CommandHandler(routingKey: 'collect', endpointId: 'your.stable.id')], or remove #[Collected].");
        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_event_handler_requires_an_explicit_endpoint_id(): void
    {
        $handler = new class () {
            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            #[EventHandler(listenTo: 'collect.event')]
            public function collect(CollectedMessages $batch): void
            {
            }
        };
        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId '') has no explicit endpointId. Open batches are stored under endpointId, so it must stay stable across deploys. Set #[EventHandler(listenTo: 'collect.event', endpointId: 'your.stable.id')], or remove #[Collected].");
        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
    public function test_collected_handler_refuses_negative_max_size_at_bootstrap(): void
    {
        $handler = new class () {
            #[Collected(maxSize: -1)]
            #[Asynchronous('batches')]
            #[InternalHandler('collect', endpointId: 'collect.handler')]
            public function collect(CollectedMessages $batch): void
            {
            }
        };
        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') maxSize is -1; maxSize must be at least 1. Set maxSize to a positive integer, omit maxSize and set maxWait, or remove #[Collected].");
        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_collected_handler_refuses_negative_max_wait_at_bootstrap(): void
    {
        $handler = new class () {
            #[Collected(maxWait: new TimeSpan(milliseconds: -1))]
            #[Asynchronous('batches')]
            #[InternalHandler('collect', endpointId: 'collect.handler')]
            public function collect(CollectedMessages $batch): void
            {
            }
        };
        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.handler') maxWait is -1 milliseconds; maxWait must be greater than 0. Set a positive TimeSpan, omit maxWait and set maxSize, or remove #[Collected].");
        EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_endpoint_ids_with_core_suffixes_compile_independent_collected_handlers(): void
    {
        $handler = new class () {
            public array $first = [];
            public array $second = [];

            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            #[CommandHandler('collect.collision.first', endpointId: 'collect.collision')]
            public function first(CollectedMessages $batch): void
            {
                $this->first[] = array_column($batch->messages, 'payload');
            }

            #[Collected(maxSize: 1)]
            #[Asynchronous('batches')]
            #[CommandHandler('collect.collision.second', endpointId: 'collect.collision.core')]
            public function second(CollectedMessages $batch): void
            {
                $this->second[] = array_column($batch->messages, 'payload');
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            licenceKey: LicenceTesting::VALID_LICENCE,
            enabledModules: [],
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
        );
        $ecotone->sendCommandWithRouting('collect.collision.first', 'one');
        $ecotone->sendCommandWithRouting('collect.collision.first', 'two');
        $ecotone->sendCommandWithRouting('collect.collision.second', 'third');
        $ecotone->run('batches');
        self::assertSame([['one', 'two']], $handler->first);
        self::assertSame([['third']], $handler->second);
    }

    public function test_collected_method_with_two_handler_attributes_is_refused_at_bootstrap(): void
    {
        $handler = new class () {
            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            #[CommandHandler('collect.command', endpointId: 'collect.command.handler')]
            #[EventHandler(listenTo: 'collect.event', endpointId: 'collect.event.handler')]
            public function collect(CollectedMessages $batch): void
            {
            }
        };
        $this->expectExceptionMessage('Collected handler ' . $handler::class . "::collect (endpointId 'collect.command.handler') has 2 handler attributes. A collected method must have exactly one #[CommandHandler], #[EventHandler] or #[InternalHandler]. Keep one handler attribute, move the other handler to a separate method, or remove #[Collected].");
        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            licenceKey: LicenceTesting::VALID_LICENCE,
            enabledModules: [],
            initialTime: new DateTimeImmutable('2026-01-01 12:00:00 UTC'),
        );
    }

    public function test_mixed_gathering_and_collecting_storage_refusal_names_both_flows(): void
    {
        $handler = new class () {
            #[CommandHandler('split', outputChannelName: 'mixed.parts')]
            public function start(array $parts): array
            {
                return $parts;
            }

            #[Splitter('mixed.parts', endpointId: 'mixed.splitter', outputChannelName: 'mixed.work', gatherChannelName: 'mixed.summary')]
            public function split(array $parts): array
            {
                return $parts;
            }

            #[InternalHandler('mixed.work', endpointId: 'mixed.worker')]
            public function work(int $part): int
            {
                return $part;
            }

            #[Gathered]
            #[Asynchronous('summaries')]
            #[InternalHandler('mixed.summary', endpointId: 'mixed.summary.handler')]
            public function summarize(GatheredParts $parts): void
            {
            }

            #[Collected(maxSize: 2)]
            #[Asynchronous('batches')]
            #[CommandHandler('collect', endpointId: 'collect.handler')]
            public function collect(CollectedMessages $batch): void
            {
            }
        };
        $this->expectExceptionMessage("Gather channel 'mixed.summary' and collected handler " . $handler::class . "::collect (endpointId 'collect.handler') require durable storage outside flow testing: groups and batches must survive restarts and be shared between consumers. Install ecotone/dbal and enable its module: add ModulePackageList::DBAL_PACKAGE to EcotoneLite::bootstrap(..., enabledModules: [...]), or 'dbal' to enabledModulePackageNames (the Symfony bundle option, config/ecotone.php key in Laravel, or EcotoneConfig argument in Tempest). Before the first message, run ecotone:migration:database:setup --initialize --feature=gathering, or generate SQL for your migrations with ecotone:migration:database:setup --sql --feature=gathering. Use EcotoneLite::bootstrapFlowTesting() for in-memory flow tests, or remove gatherChannelName from #[Splitter] and #[Collected] from the service handler.");
        EcotoneLite::bootstrap(
            classesToResolve: [$handler::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('summaries'), SimpleMessageChannelBuilder::createQueueChannel('batches')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}
