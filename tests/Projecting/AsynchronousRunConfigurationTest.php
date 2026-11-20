<?php

declare(strict_types=1);

namespace Test\Ecotone\Projecting;

use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Projecting\FromStream;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Projecting\ProjectionBackfill;
use Ecotone\Api\Projecting\ProjectionRebuild;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 *
 * @internal
 */
final class AsynchronousRunConfigurationTest extends TestCase
{
    public function test_asynchronous_rebuild_without_durable_storage_is_refused_outside_flow_testing_with_the_ways_out(): void
    {
        $projection = new #[Projection('orders'), FromStream('orders_stream'), ProjectionRebuild(asyncChannelName: 'rebuild_channel')] class () {
            #[EventHandler('order.placed')]
            public function whenPlaced(array $event): void
            {
            }
        };

        $this->expectExceptionMessage("Projection 'orders' runs its rebuild asynchronously on 'rebuild_channel', so the run is tracked as a gathering group that must survive restarts and be shared between consumers, which needs durable storage when bootstrapping without test configuration. Install ecotone/dbal and enable its module: add ModulePackageList::DBAL_PACKAGE to EcotoneLite::bootstrap(..., enabledModules: [...]), or 'dbal' to enabledModulePackageNames (the Symfony bundle option, config/ecotone.php key in Laravel, or EcotoneConfig argument in Tempest). Before the first rebuild, run ecotone:migration:database:setup --initialize --feature=gathering, or generate SQL for your migrations with ecotone:migration:database:setup --sql --feature=gathering. Use EcotoneLite::bootstrapFlowTesting() for in-memory flow tests, or remove asyncChannelName from #[ProjectionRebuild] to run the rebuild in the calling process.");

        EcotoneLite::bootstrap([$projection::class], [$projection], extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('rebuild_channel')], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE);
    }

    public function test_asynchronous_backfill_without_durable_storage_is_refused_outside_flow_testing_with_the_ways_out(): void
    {
        $projection = new #[Projection('orders'), FromStream('orders_stream'), ProjectionBackfill(asyncChannelName: 'backfill_channel')] class () {
            #[EventHandler('order.placed')]
            public function whenPlaced(array $event): void
            {
            }
        };

        $this->expectExceptionMessage("Projection 'orders' runs its backfill asynchronously on 'backfill_channel'");
        $this->expectExceptionMessage('or remove asyncChannelName from #[ProjectionBackfill] to run the backfill in the calling process.');

        EcotoneLite::bootstrap([$projection::class], [$projection], extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('backfill_channel')], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE);
    }

    public function test_an_unregistered_rebuild_channel_is_refused_naming_the_attribute_and_the_ways_out(): void
    {
        $projection = new #[Projection('orders'), FromStream('orders_stream'), ProjectionRebuild(asyncChannelName: 'rebuild_channel')] class () {
            #[EventHandler('order.placed')]
            public function whenPlaced(array $event): void
            {
            }
        };

        $this->expectExceptionMessage("Projection 'orders' runs its rebuild on 'rebuild_channel' (asyncChannelName of #[ProjectionRebuild]), which is not registered as a message channel. Register it from a #[ServiceContext] method, for example DbalBackedMessageChannelBuilder::create('rebuild_channel'), or remove asyncChannelName from #[ProjectionRebuild] to run the rebuild in the calling process.");

        EcotoneLite::bootstrapFlowTesting([$projection::class], [$projection], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE);
    }

    public function test_an_unregistered_backfill_channel_is_refused_naming_the_attribute_and_the_ways_out(): void
    {
        $projection = new #[Projection('orders'), FromStream('orders_stream'), ProjectionBackfill(asyncChannelName: 'backfill_channel')] class () {
            #[EventHandler('order.placed')]
            public function whenPlaced(array $event): void
            {
            }
        };

        $this->expectExceptionMessage("Projection 'orders' runs its backfill on 'backfill_channel' (asyncChannelName of #[ProjectionBackfill]), which is not registered as a message channel.");

        EcotoneLite::bootstrapFlowTesting([$projection::class], [$projection], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE);
    }

    public function test_an_unregistered_batch_completion_channel_is_refused_naming_the_attribute_and_the_ways_out(): void
    {
        $projection = new #[Projection('orders'), FromStream('orders_stream'), ProjectionRebuild(asyncChannelName: 'rebuild_channel', batchCompletionChannelName: 'batch_completion_channel')] class () {
            #[EventHandler('order.placed')]
            public function whenPlaced(array $event): void
            {
            }
        };

        $this->expectExceptionMessage("Projection 'orders' consumes the batch reports of its rebuild on 'batch_completion_channel' (batchCompletionChannelName of #[ProjectionRebuild]), which is not registered as a message channel. Register it from a #[ServiceContext] method, for example DbalBackedMessageChannelBuilder::create('batch_completion_channel'), or remove batchCompletionChannelName to consume them on 'rebuild_channel'.");

        EcotoneLite::bootstrapFlowTesting([$projection::class], [$projection], extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('rebuild_channel')], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE);
    }
}
