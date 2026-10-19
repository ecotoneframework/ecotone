<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Api\Modelling\WithEvents;
use Ecotone\Test\LicenceTesting;
use Generator;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowSourcedTest extends TestCase
{
    public function test_event_sourced_single_event_shows_its_emitted_event(): void
    {
        $aggregate = new #[EventSourcingAggregate] class () {
            use WithAggregateVersioning;

            #[Identifier]
            private string $id = 'order';

            #[CommandHandler('sourced.create')]
            public static function create(): SourcedEvent
            {
                return new SourcedEvent();
            }

            #[Apply]
            public function apply(SourcedEvent $event): void
            {
                $this->id = 'order';
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$aggregate::class], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $aggregate::class . '::create', 'message:' . SourcedEvent::class));
        self::assertSame(['inferred' => true, 'via' => 'return'], $edges[0]->attributes);
    }

    public function test_event_sourced_event_array_shows_its_emitted_event(): void
    {
        $aggregate = new #[EventSourcingAggregate] class () {
            use WithAggregateVersioning;

            #[Identifier]
            private string $id = 'order';

            #[CommandHandler('sourced.create')]
            public static function create(): array
            {
                return [new SourcedEvent()];
            }

            #[Apply]
            public function apply(SourcedEvent $event): void
            {
                $this->id = 'order';
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$aggregate::class], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $aggregate::class . '::create', 'message:' . SourcedEvent::class));
        self::assertSame(['inferred' => true, 'via' => 'return'], $edges[0]->attributes);
    }

    public function test_event_sourced_event_generator_shows_its_emitted_event(): void
    {
        $aggregate = new #[EventSourcingAggregate] class () {
            use WithAggregateVersioning;

            #[Identifier]
            private string $id = 'order';

            #[CommandHandler('sourced.create')]
            public static function create(): Generator
            {
                yield new SourcedEvent();
            }

            #[Apply]
            public function apply(SourcedEvent $event): void
            {
                $this->id = 'order';
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$aggregate::class], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $aggregate::class . '::create', 'message:' . SourcedEvent::class));
        self::assertSame(['inferred' => true, 'via' => 'return'], $edges[0]->attributes);
    }

    public function test_event_sourced_internal_recorder_shows_its_emitted_event(): void
    {
        $aggregate = new #[EventSourcingAggregate(withInternalEventRecorder: true)] class () {
            use WithAggregateVersioning;
            use WithEvents;

            #[Identifier]
            private string $id = 'order';

            #[CommandHandler('sourced.create')]
            public static function create(): static
            {
                $aggregate = new static();
                $aggregate->recordThat(new SourcedEvent());

                return $aggregate;
            }

            #[Apply]
            public function apply(SourcedEvent $event): void
            {
                $this->id = 'order';
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$aggregate::class], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $aggregate::class . '::create', 'message:' . SourcedEvent::class));
        self::assertSame(['inferred' => true, 'via' => 'recordThat'], $edges[0]->attributes);
    }

}

final class SourcedEvent
{
}
