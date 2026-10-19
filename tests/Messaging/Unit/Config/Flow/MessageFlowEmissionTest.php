<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\NamedEvent;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Gateway\QueryBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Api\Messaging\Flow\FlowNodeKind;
use Ecotone\Api\Modelling\WithEvents;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowEmissionTest extends TestCase
{
    public function test_event_static_factory_is_shown_as_published(): void
    {
        $service = new class () {
            #[CommandHandler('factory.external')]
            public function handle(array $payload, EventBus $bus): void
            {
                $bus->publish(EmissionEvent::create());
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . EmissionEvent::class));
        self::assertSame(['inferred' => true, 'via' => 'publish'], $edges[0]->attributes);
    }

    public function test_aggregate_static_helper_event_is_shown_as_recorded_on_the_fresh_instance(): void
    {
        $aggregate = new #[Aggregate] class () {
            use WithEvents;

            #[Identifier]
            private string $id = 'order';

            #[CommandHandler('factory.local')]
            public static function create(): static
            {
                $order = new static();
                $order->recordThat(self::placed());

                return $order;
            }

            private static function placed(): EmissionEvent
            {
                return new EmissionEvent();
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$aggregate::class], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $aggregate::class . '::create', 'message:' . EmissionEvent::class));
        self::assertSame(['inferred' => true, 'via' => 'recordThat'], $edges[0]->attributes);
    }
    public function test_event_assigned_before_publication_is_shown_as_published(): void
    {
        $service = new class () {
            #[CommandHandler('event.variable')]
            public function handle(array $payload, EventBus $bus): void
            {
                $event = new EmissionEvent();
                $bus->publish($event);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . EmissionEvent::class));
        self::assertSame('publish', $edges[0]->attributes['via']);
    }

    public function test_event_returned_from_private_helper_is_shown_as_published(): void
    {
        $service = new class () {
            #[CommandHandler('event.helper')]
            public function handle(array $payload, EventBus $bus): void
            {
                $bus->publish($this->event());
            }

            private function event(): EmissionEvent
            {
                return new EmissionEvent();
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . EmissionEvent::class));
        self::assertSame('publish', $edges[0]->attributes['via']);
    }

    public function test_this_record_that_is_shown_as_recorded_without_subscribers(): void
    {
        $aggregate = new #[Aggregate] class () {
            use WithEvents;

            #[Identifier]
            private string $id = 'order';

            #[CommandHandler('record.instance')]
            public function record(): void
            {
                $this->recordThat(new EmissionEvent());
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$aggregate::class], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $aggregate::class . '::record', 'message:' . EmissionEvent::class));
        self::assertSame(['inferred' => true, 'via' => 'recordThat'], $edges[0]->attributes);
    }

    public function test_shared_recursive_helper_yields_one_emission_for_each_handler(): void
    {
        $service = new class () {
            #[CommandHandler('helper.first')]
            public function first(array $payload, EventBus $bus): void
            {
                $bus->publish($this->event(2));
            }

            #[CommandHandler('helper.second')]
            public function second(array $payload, EventBus $bus): void
            {
                $bus->publish($this->event(3));
            }

            private function event(int $depth): EmissionEvent
            {
                return $depth > 0 ? $this->event($depth - 1) : new EmissionEvent();
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        foreach (['first', 'second'] as $method) {
            self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::' . $method));
            self::assertSame('message:' . EmissionEvent::class, $edges[0]->to);
            self::assertSame(['inferred' => true, 'via' => 'publish'], $edges[0]->attributes);
        }
    }

    public function test_class_constant_routing_key_is_shown_as_published(): void
    {
        $service = new class () {
            #[CommandHandler('route.constant')]
            public function handle(EventBus $bus): void
            {
                $bus->publishWithRouting(EmissionEvent::NAME, []);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:emission.created'));
    }

    public function test_backed_enum_routing_key_is_shown_as_published(): void
    {
        $service = new class () {
            #[CommandHandler('route.enum')]
            public function handle(EventBus $bus): void
            {
                $bus->publishWithRouting(EmissionRoute::CREATED->value, []);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:emission.enum'));
    }

    public function test_named_event_is_one_node_reached_by_its_class_and_its_name(): void
    {
        $service = new class () {
            #[CommandHandler('named.publish')]
            public function publish(EventBus $bus): void
            {
                $bus->publish(new NamedEmissionEvent());
                $bus->publishWithRouting('emission.named', []);
            }

            #[EventHandler]
            public function typed(NamedEmissionEvent $event): void
            {
            }

            #[EventHandler('emission.named')]
            public function named(array $payload): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $event = 'message:' . NamedEmissionEvent::class;
        self::assertFalse($graph->hasNode('message:emission.named'));
        self::assertSame('emission.named', $graph->node($event)->attributes['namedAs']);
        foreach (['typed', 'named'] as $method) {
            self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, $event, 'handler:' . $service::class . '::' . $method));
        }
        self::assertSame([$event], array_values(array_unique(array_column($graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::publish'), 'to'))));
    }

    public function test_named_event_handled_only_by_its_name_is_reached_from_its_publisher(): void
    {
        $service = new class () {
            #[CommandHandler('named.only')]
            public function publish(EventBus $bus): void
            {
                $bus->publish(new NamedEmissionEvent());
            }

            #[EventHandler('emission.named')]
            public function named(array $payload): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class, NamedEmissionEvent::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $event = 'message:' . NamedEmissionEvent::class;
        self::assertFalse($graph->hasNode('message:emission.named'));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::publish', $event));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, $event, 'handler:' . $service::class . '::named'));
    }

    public function test_command_sent_by_class_has_the_correct_message_kind(): void
    {
        $service = new class () {
            #[CommandHandler('outbound.trigger')]
            public function handle(CommandBus $bus): void
            {
                $bus->send(new EmissionCommand());
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame(FlowNodeKind::COMMAND, $graph->node('message:' . EmissionCommand::class)->kind);
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . EmissionCommand::class));
    }

    public function test_command_sent_by_routing_key_has_the_correct_message_kind(): void
    {
        $service = new class () {
            #[CommandHandler('outbound.trigger')]
            public function handle(CommandBus $bus): void
            {
                $bus->sendWithRouting('outbound.command', []);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame(FlowNodeKind::COMMAND, $graph->node('message:outbound.command')->kind);
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:outbound.command'));
    }

    public function test_query_sent_by_class_has_the_correct_message_kind(): void
    {
        $service = new class () {
            #[CommandHandler('outbound.trigger')]
            public function handle(QueryBus $bus): void
            {
                $bus->send(new EmissionQuery());
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame(FlowNodeKind::QUERY, $graph->node('message:' . EmissionQuery::class)->kind);
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . EmissionQuery::class));
    }

    public function test_query_sent_by_routing_key_has_the_correct_message_kind(): void
    {
        $service = new class () {
            #[CommandHandler('outbound.trigger')]
            public function handle(QueryBus $bus): void
            {
                $bus->sendWithRouting('outbound.query', []);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame(FlowNodeKind::QUERY, $graph->node('message:outbound.query')->kind);
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:outbound.query'));
    }

    public function test_single_quoted_routing_keys_keep_literal_backslash_sequences(): void
    {
        $service = new class () {
            #[CommandHandler('literal.single')]
            public function handle(EventBus $bus): void
            {
                $bus->publishWithRouting('literal\new\thing', []);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:literal\new\thing'));
    }

    public function test_double_quoted_routing_keys_decode_php_escapes_and_unicode(): void
    {
        $service = new class () {
            #[CommandHandler('literal.double')]
            public function handle(EventBus $bus): void
            {
                $bus->publishWithRouting("literal\path\n\e\u{1F680}", []);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', "message:literal\path\n\e\u{1F680}"));
    }

}

final class EmissionEvent
{
    public const NAME = 'emission.created';

    public static function create(): static
    {
        return new static();
    }
}

enum EmissionRoute: string
{
    case CREATED = 'emission.enum';
}

#[NamedEvent('emission.named')]
final class NamedEmissionEvent
{
}

final class EmissionCommand
{
}

final class EmissionQuery
{
}
