<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionBoundary;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowDecisionTest extends TestCase
{
    public function test_decision_boundary_handler_return_is_shown_as_emitted_event(): void
    {
        $service = new class () {
            #[DecisionBoundary]
            public static function boundary(DecisionCommandForFlow $command): EventCriteria
            {
                return EventCriteria::tag('order', $command->orderId);
            }

            #[CommandHandler]
            public function handle(DecisionCommandForFlow $command): array
            {
                return [new DecisionEventForFlow($command->orderId)];
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$service::class, DecisionEventForFlow::class],
            containerOrAvailableServices: [$service],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        )->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . DecisionEventForFlow::class));
        self::assertSame(['inferred' => true, 'via' => 'return'], $edges[0]->attributes);
    }

    public function test_decision_model_handler_return_is_shown_as_emitted_event(): void
    {
        $service = new class () {
            #[CommandHandler]
            public function handle(DecisionCommandForFlow $command, DecisionStateForFlow $state): array
            {
                return [new DecisionEventForFlow($command->orderId)];
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$service::class, DecisionStateForFlow::class, DecisionEventForFlow::class],
            containerOrAvailableServices: [$service],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        )->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . DecisionEventForFlow::class));
        self::assertSame(['inferred' => true, 'via' => 'return'], $edges[0]->attributes);
    }
}

final class DecisionCommandForFlow
{
    public function __construct(public string $orderId)
    {
    }
}

final class DecisionEventForFlow
{
    public function __construct(#[EventTag('order')] public string $orderId)
    {
    }
}

#[DecisionModel]
final class DecisionStateForFlow
{
    #[Apply]
    public function apply(DecisionEventForFlow $event): void
    {
    }
}
