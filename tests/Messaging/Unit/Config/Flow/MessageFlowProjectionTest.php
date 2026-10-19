<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\Polling;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Api\Projecting\FromAggregateStream;
use Ecotone\Api\Projecting\FromStream;
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
