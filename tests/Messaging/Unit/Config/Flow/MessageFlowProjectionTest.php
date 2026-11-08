<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\Polling;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Api\Projecting\FromAggregateStream;
use Ecotone\Api\Projecting\FromStream;
use Ecotone\Api\Projecting\FromTaggedStream;
use Ecotone\Api\Projecting\Partitioned;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Projecting\ProjectionBackfill;
use Ecotone\Api\Projecting\ProjectionRebuild;
use Ecotone\Api\Projecting\Streaming;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowProjectionTest extends TestCase
{
    public function test_polling_projection_keeps_its_endpoint_and_multiple_event_handlers(): void
    {
        $projection = new #[Projection('polling.view'), FromStream('orders'), Polling('polling.endpoint')] class () {
            #[EventHandler('order.created')]
            public function created(array $event): void
            {
            }

            #[EventHandler('order.changed')]
            public function changed(array $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$projection::class], [$projection], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame('polling.endpoint', $graph->node('projection:polling.view')->attributes['endpointId'] ?? null);
        foreach (['created', 'changed'] as $method) {
            self::assertSame('projection:polling.view', $graph->node('handler:' . $projection::class . '::' . $method)->attributes['owner']);
        }
    }

    public function test_streaming_projection_is_linked_to_its_source_channel(): void
    {
        $projection = new #[Projection('streaming.view'), Streaming('projection.stream')] class () {
            #[EventHandler('order.created')]
            public function created(array $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$projection::class],
            containerOrAvailableServices: [$projection],
            extensionObjects: [SimpleMessageChannelBuilder::createStreamingChannel('projection.stream')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        )->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::INPUT, 'channel:projection.stream', 'projection:streaming.view'));
        self::assertSame('projection:streaming.view', $graph->node('handler:' . $projection::class . '::created')->attributes['owner']);
    }

    public function test_projection_backfilled_and_rebuilt_asynchronously_sends_through_and_is_fed_by_its_channels(): void
    {
        $projection = new #[Projection('replayed.view'), FromStream('orders'), ProjectionBackfill(asyncChannelName: 'backfill'), ProjectionRebuild(asyncChannelName: 'rebuild')] class () {
            #[EventHandler('order.created')]
            public function created(array $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$projection::class],
            containerOrAvailableServices: [$projection],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('backfill'), SimpleMessageChannelBuilder::createQueueChannel('rebuild')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        )->getMessageFlowGraph();

        foreach (['backfill', 'rebuild'] as $channel) {
            self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ASYNCHRONOUS, 'projection:replayed.view', 'channel:' . $channel));
            self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::INPUT, 'channel:' . $channel, 'projection:replayed.view'));
        }
    }

    public function test_mermaid_draws_the_projection_as_the_subgraph_around_its_event_handlers_instead_of_an_edge_into_it(): void
    {
        $projection = new #[Projection('seat.map'), FromStream('seats')] class () {
            #[EventHandler('seat.released')]
            public function seatReleased(array $event): void
            {
            }
        };
        $mermaid = EcotoneLite::bootstrapFlowTesting([$projection::class], [$projection], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph()->toMermaid();

        self::assertSame(1, preg_match('/^    subgraph (n\d+) \["Projection: seat\.map"\]\n        n\d+.*::seatReleased"\]\n    end$/m', $mermaid, $match));
        self::assertDoesNotMatchRegularExpression('/^    \w+ \S+ ' . $match[1] . '$/m', $mermaid);
    }

    public function test_projection_from_aggregate_stream_keeps_event_handler_ownership(): void
    {
        $projection = new #[Projection('aggregate.view'), FromAggregateStream(ProjectionAggregateForFlow::class)] class () {
            #[EventHandler]
            public function created(ProjectionEventForFlow $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$projection::class, ProjectionAggregateForFlow::class], [$projection], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertTrue($graph->hasNode('aggregate:' . ProjectionAggregateForFlow::class));
        self::assertSame('projection:aggregate.view', $graph->node('handler:' . $projection::class . '::created')->attributes['owner']);
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:' . ProjectionEventForFlow::class, 'handler:' . $projection::class . '::created'));
    }

    public function test_a_followed_event_reaches_the_projection_that_reads_it(): void
    {
        $projection = new #[Projection('seat.map'), FromStream('seats'), FromStream('concerts')] class () {
            #[EventHandler]
            public function seatHeld(ProjectionEventForFlow $event): void
            {
            }

            #[EventHandler('seat.released')]
            public function seatReleased(array $event): void
            {
            }

            #[EventHandler]
            public function seatChanged(SeatSwappedForFlow|TaggedSeatHeldForFlow $event): void
            {
            }

            #[QueryHandler('seat.map')]
            public function seatMap(): array
            {
                return [];
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$projection::class], [$projection], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $handler = fn (string $method) => 'handler:' . $projection::class . '::' . $method;
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:' . ProjectionEventForFlow::class, $handler('seatHeld')));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::PROJECTS_INTO, $handler('seatHeld'), 'projection:seat.map'));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::PROJECTS_INTO, $handler('seatReleased'), 'projection:seat.map'));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:' . SeatSwappedForFlow::class, $handler('seatChanged')));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:' . TaggedSeatHeldForFlow::class, $handler('seatChanged')));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::PROJECTS_INTO, $handler('seatChanged'), 'projection:seat.map'));
        self::assertSame([], $graph->edgesOfKind(FlowEdgeKind::PROJECTS_INTO, $handler('seatMap')));
        self::assertSame(['seats', 'concerts'], $graph->node('projection:seat.map')->attributes['fromStreams']);
    }

    public function test_projection_names_the_tag_and_the_aggregate_it_projects_from(): void
    {
        $tagged = new #[Projection('tagged.view'), FromTaggedStream('concert'), Partitioned] class () {
            #[EventHandler]
            public function seatHeld(TaggedSeatHeldForFlow $event): void
            {
            }
        };
        $aggregate = new #[Projection('aggregate.source.view'), FromAggregateStream(ProjectionAggregateForFlow::class)] class () {
            #[EventHandler]
            public function created(ProjectionEventForFlow $event): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$tagged::class, $aggregate::class, ProjectionAggregateForFlow::class, TaggedSeatHeldForFlow::class], [$tagged, $aggregate], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame(['concert'], $graph->node('projection:tagged.view')->attributes['fromTags']);
        self::assertSame([ProjectionAggregateForFlow::class], $graph->node('projection:aggregate.source.view')->attributes['fromAggregateStreams']);
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::PROJECTS_INTO, 'handler:' . $tagged::class . '::seatHeld', 'projection:tagged.view'));
    }
}

#[EventSourcingAggregate]
final class ProjectionAggregateForFlow
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $id = 'order';

    #[CommandHandler('projection.create')]
    public static function create(): array
    {
        return [new ProjectionEventForFlow()];
    }

    #[Apply]
    public function apply(ProjectionEventForFlow $event): void
    {
        $this->id = 'order';
    }
}

final class ProjectionEventForFlow
{
}

final class SeatSwappedForFlow
{
}

final class TaggedSeatHeldForFlow
{
    public function __construct(#[EventTag('concert')] public string $concertId)
    {
    }
}
