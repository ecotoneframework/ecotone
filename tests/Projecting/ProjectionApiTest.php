<?php

declare(strict_types=1);

namespace Test\Ecotone\Projecting;

use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Polling;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Projecting\FromStream;
use Ecotone\Api\Projecting\Partitioned;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Projecting\ProjectionDeployment;
use Ecotone\Api\Projecting\Streaming;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Consumer\ConsumerPositionTracker;
use Ecotone\Messaging\Consumer\InMemory\InMemoryConsumerPositionTracker;
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
final class ProjectionApiTest extends TestCase
{
    public function test_it_throws_exception_when_polling_and_streaming_are_used_together(): void
    {
        $projection = new #[Projection('test_projection'), Polling('test_endpoint'), Streaming('test_channel')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'test_projection' cannot use both #[Polling] and #[Streaming] attributes.");

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_it_throws_exception_when_polling_and_partitioned_are_used_together(): void
    {
        $projection = new #[Projection('test_projection'), Polling('test_endpoint'), Partitioned] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'test_projection' cannot use both #[Polling] and #[Partitioned] attributes.");

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_it_throws_exception_when_partitioned_projection_asks_for_manual_kick_off(): void
    {
        $projection = new #[Projection('test_projection'), FromStream('test_stream'), Partitioned, ProjectionDeployment(manualKickOff: true)] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'test_projection' combines #[Partitioned] with #[ProjectionDeployment(manualKickOff: true)], but a partitioned projection initializes each partition on that partition's first event, so it cannot wait to be kicked off and manualKickOff would be ignored. Either remove manualKickOff: true from #[ProjectionDeployment] and let each partition initialize on its first event, or remove #[Partitioned] to keep the projection waiting until it is kicked off with ecotone:projection:init test_projection.");

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_it_throws_exception_when_partitioned_and_streaming_are_used_together(): void
    {
        $projection = new #[Projection('test_projection'), Partitioned, Streaming('test_channel')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'test_projection' cannot use both #[Partitioned] and #[Streaming] attributes.");

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_streaming_projection_does_not_require_from_stream_attribute(): void
    {
        $positionTracker = new InMemoryConsumerPositionTracker();

        $projection = new #[Projection('streaming_projection'), Streaming('streaming_channel')] class {
            public array $events = [];

            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->events[] = $event;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection, ConsumerPositionTracker::class => $positionTracker],
            extensionObjects: [SimpleMessageChannelBuilder::createStreamingChannel('streaming_channel', conversionMediaType:MediaType::createApplicationXPHP())],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $this->assertNotNull($ecotone);
    }

    public function test_non_streaming_projection_requires_from_stream_attribute(): void
    {
        $projection = new #[Projection('non_streaming_projection')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'non_streaming_projection' must have at least one #[FromStream] or #[FromAggregateStream] attribute");

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}
