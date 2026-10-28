<?php

declare(strict_types=1);

namespace Test\Ecotone\Projecting;

use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Polling;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Projecting\FromStream;
use Ecotone\Api\Projecting\FromTaggedStream;
use Ecotone\Api\Projecting\Partitioned;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Projecting\ProjectionDeployment;
use Ecotone\Api\Projecting\Streaming;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Support\LicensingException;
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
final class TagPartitionedProjectionConfigurationTest extends TestCase
{
    public function test_a_tagged_stream_without_partitioning_is_refused_naming_both_ways_out(): void
    {
        $projection = new #[Projection('concert_seats'), FromTaggedStream('concert')] class () {
            #[EventHandler]
            public function whenReserved(SeatReservedForTagConfiguration $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'concert_seats' reads #[FromTaggedStream('concert')] without #[Partitioned], but a tagged stream is read one value of the tag at a time, positioned by that value's tag sequence, so it has no single position to read it globally by. Either add #[Partitioned] to keep one partition per value of 'concert', or read the events with #[FromStream] for a projection over the whole stream.");

        $this->bootstrap($projection);
    }

    public function test_a_partition_header_name_on_a_tagged_stream_is_refused(): void
    {
        $projection = new #[Projection('concert_seats'), FromTaggedStream('concert'), Partitioned('concertId')] class () {
            #[EventHandler]
            public function whenReserved(SeatReservedForTagConfiguration $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'concert_seats' reads #[FromTaggedStream('concert')] with #[Partitioned('concertId')], but the partition of a tagged stream is the value of the tag 'concert', so a partition header has nothing to name. Use #[Partitioned] without a header name.");

        $this->bootstrap($projection);
    }

    public function test_two_tagged_streams_on_one_projection_are_refused(): void
    {
        $projection = new #[Projection('concert_seats'), FromTaggedStream('concert'), FromTaggedStream('venue'), Partitioned] class () {
            #[EventHandler]
            public function whenReserved(SeatReservedForTagConfiguration $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'concert_seats' declares #[FromTaggedStream] more than once (concert, venue), but its partitions are the values of one tag, and values of two tags would share partition keys. Keep one #[FromTaggedStream], or split the projection into one projection per tag.");

        $this->bootstrap($projection);
    }

    public function test_a_tagged_stream_combined_with_another_source_is_refused(): void
    {
        $projection = new #[Projection('concert_seats'), FromTaggedStream('concert'), FromStream('ecotone_event_stream'), Partitioned] class () {
            #[EventHandler]
            public function whenReserved(SeatReservedForTagConfiguration $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'concert_seats' combines #[FromTaggedStream('concert')] with #[FromStream] or #[FromAggregateStream], but its partitions are the values of the tag 'concert', and the other source is partitioned differently or not at all. Keep #[FromTaggedStream] alone, or move the other source into a projection of its own.");

        $this->bootstrap($projection);
    }

    public function test_a_tag_no_event_declares_is_refused(): void
    {
        $projection = new #[Projection('concert_seats'), FromTaggedStream('concrt'), Partitioned] class () {
            #[EventHandler]
            public function whenReserved(SeatReservedForTagConfiguration $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'concert_seats' reads #[FromTaggedStream('concrt')], but no event declares #[EventTag('concrt')], so the projection would never see an event. Name a tag an event declares with #[EventTag] (declared tags: concert), or add #[EventTag('concrt')] to the events the projection handles.");

        $this->bootstrap($projection);
    }

    public function test_a_filter_only_tag_is_refused_because_it_cannot_position_a_partition(): void
    {
        $projection = new #[Projection('concert_seats'), FromTaggedStream('concert'), Partitioned] class () {
            #[EventHandler]
            public function whenReserved(SeatReservedForTagConfiguration $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'concert_seats' reads #[FromTaggedStream('concert')], but 'concert' is a filter-only tag: its events carry no tag sequence (it is always 0), so a partition of it has no position to resume from. Either remove 'concert' from DynamicConsistencyBoundaryConfiguration::withFilterOnlyTags(), or partition the projection by a counted tag.");

        $this->bootstrap($projection, DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withFilterOnlyTags(['concert']));
    }

    public function test_a_tagged_stream_with_streaming_is_refused(): void
    {
        $projection = new #[Projection('concert_seats'), FromTaggedStream('concert'), Partitioned, Streaming('concert_stream')] class () {
            #[EventHandler]
            public function whenReserved(SeatReservedForTagConfiguration $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'concert_seats' cannot use both #[Partitioned] and #[Streaming] attributes.");

        $this->bootstrap($projection);
    }

    public function test_a_tagged_stream_with_polling_is_refused(): void
    {
        $projection = new #[Projection('concert_seats'), FromTaggedStream('concert'), Partitioned, Polling('concert_seats_poller')] class () {
            #[EventHandler]
            public function whenReserved(SeatReservedForTagConfiguration $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'concert_seats' cannot use both #[Polling] and #[Partitioned] attributes.");

        $this->bootstrap($projection);
    }

    public function test_a_tagged_stream_with_manual_kick_off_is_refused(): void
    {
        $projection = new #[Projection('concert_seats'), FromTaggedStream('concert'), Partitioned, ProjectionDeployment(manualKickOff: true)] class () {
            #[EventHandler]
            public function whenReserved(SeatReservedForTagConfiguration $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'concert_seats' combines #[Partitioned] with #[ProjectionDeployment(manualKickOff: true)]");

        $this->bootstrap($projection);
    }

    public function test_a_tagged_stream_without_an_enterprise_licence_is_refused(): void
    {
        $projection = new #[Projection('concert_seats'), FromTaggedStream('concert'), Partitioned] class () {
            #[EventHandler]
            public function whenReserved(SeatReservedForTagConfiguration $event): void
            {
            }
        };

        $this->expectException(LicensingException::class);
        $this->expectExceptionMessage("#[FromTaggedStream] on projection 'concert_seats' requires Ecotone Enterprise licence.");

        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$projection::class, SeatReservedForTagConfiguration::class],
            containerOrAvailableServices: [$projection],
            enabledModules: [],
        );
    }

    private function bootstrap(object $projection, ?DynamicConsistencyBoundaryConfiguration $dynamicConsistencyBoundary = null): void
    {
        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$projection::class, SeatReservedForTagConfiguration::class],
            containerOrAvailableServices: [$projection],
            extensionObjects: [$dynamicConsistencyBoundary ?? DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

final readonly class SeatReservedForTagConfiguration
{
    public function __construct(
        #[EventTag('concert')] public string $concertId,
        public string $seatId,
    ) {
    }
}
