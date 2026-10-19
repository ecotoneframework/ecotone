<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Saga;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Api\Messaging\Flow\FlowNodeKind;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowStructureTest extends TestCase
{
    public function test_saga_command_handler_shows_its_owner_and_emitted_command(): void
    {
        $saga = new #[Saga] class () {
            #[Identifier]
            private string $id = 'order';

            #[CommandHandler('saga.command')]
            public function handle(CommandBus $bus): void
            {
                $bus->sendWithRouting('saga.next', []);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$saga::class], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $id = 'handler:' . $saga::class . '::handle';
        self::assertSame(FlowNodeKind::SAGA, $graph->node('saga:' . $saga::class)->kind);
        self::assertSame('saga:' . $saga::class, $graph->node($id)->attributes['owner']);
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::EMITS, $id, 'message:saga.next'));
    }

    public function test_saga_event_handler_shows_its_owner_and_emitted_command(): void
    {
        $saga = new #[Saga] class () {
            #[Identifier]
            private string $id = 'order';

            #[EventHandler('saga.event')]
            public function handle(CommandBus $bus): void
            {
                $bus->sendWithRouting('saga.next', []);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$saga::class], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $id = 'handler:' . $saga::class . '::handle';
        self::assertSame('saga:' . $saga::class, $graph->node($id)->attributes['owner']);
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:saga.event', $id));
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::EMITS, $id, 'message:saga.next'));
    }
    public function test_aggregate_query_is_owned_by_the_aggregate(): void
    {
        $aggregate = new #[Aggregate] class () {
            #[Identifier]
            private string $id = 'order';

            #[QueryHandler('aggregate.status')]
            public function status(): string
            {
                return $this->id;
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$aggregate::class], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $id = 'handler:' . $aggregate::class . '::status';
        self::assertSame(FlowNodeKind::QUERY_HANDLER, $graph->node($id)->kind);
        self::assertSame('aggregate:' . $aggregate::class, $graph->node($id)->attributes['owner']);
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:aggregate.status', $id));
    }

    public function test_aggregate_factory_and_action_both_receive_the_same_command(): void
    {
        $aggregate = new #[Aggregate] class () {
            #[Identifier]
            private string $id = 'order';

            #[CommandHandler('aggregate.upsert')]
            public static function create(): static
            {
                return new static();
            }

            #[CommandHandler('aggregate.upsert')]
            public function update(): void
            {
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$aggregate::class], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(2, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:aggregate.upsert'));
        foreach (['create', 'update'] as $method) {
            self::assertSame('aggregate:' . $aggregate::class, $graph->node('handler:' . $aggregate::class . '::' . $method)->attributes['owner']);
        }
    }

}
