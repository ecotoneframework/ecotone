<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowCollaboratorTest extends TestCase
{
    public function test_publication_through_service_property_is_shown(): void
    {
        $service = new class () {
            public EventBus $bus;

            #[CommandHandler('collaborator.service_property')]
            public function handle(): void
            {
                $this->bus->publish(new CollaboratorEvent());
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . CollaboratorEvent::class));
        self::assertSame(['inferred' => true, 'via' => 'publish'], $edges[0]->attributes);
    }

    public function test_publication_through_nullable_property_is_shown(): void
    {
        $service = new class () {
            public ?EventBus $bus;

            #[CommandHandler('collaborator.nullable_property')]
            public function handle(): void
            {
                $this->bus?->publish(new CollaboratorEvent());
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . CollaboratorEvent::class));
        self::assertSame(['inferred' => true, 'via' => 'publish'], $edges[0]->attributes);
    }

    public function test_publication_through_union_property_is_shown(): void
    {
        $service = new class () {
            public CollaboratorEvent|EventBus $bus;

            #[CommandHandler('collaborator.union_property')]
            public function handle(): void
            {
                $this->bus->publish(new CollaboratorEvent());
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . CollaboratorEvent::class));
        self::assertSame(['inferred' => true, 'via' => 'publish'], $edges[0]->attributes);
    }

    public function test_publication_through_extended_bus_property_is_shown(): void
    {
        $service = new class () {
            public ExtendedEventBusForFlow $bus;

            #[CommandHandler('collaborator.extended_bus_property')]
            public function handle(): void
            {
                $this->bus->publish(new CollaboratorEvent());
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . CollaboratorEvent::class));
        self::assertSame(['inferred' => true, 'via' => 'publish'], $edges[0]->attributes);
    }

    public function test_publication_through_method_parameter_is_shown(): void
    {
        $service = new class () {
            #[CommandHandler('collaborator.method_parameter')]
            public function handle(EventBus $bus): void
            {
                $bus->publish(new CollaboratorEvent());
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . CollaboratorEvent::class));
        self::assertSame(['inferred' => true, 'via' => 'publish'], $edges[0]->attributes);
    }

    public function test_publication_through_parent_property_is_shown(): void
    {
        $service = new class () extends EventBusOwnerForFlow {
            #[CommandHandler('collaborator.parent_property')]
            public function handle(): void
            {
                $this->bus->publish(new CollaboratorEvent());
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . CollaboratorEvent::class));
        self::assertSame(['inferred' => true, 'via' => 'publish'], $edges[0]->attributes);
    }

    public function test_publication_through_promoted_property_is_shown(): void
    {
        $bus = EcotoneLite::bootstrapFlowTesting(enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getGateway(EventBus::class);
        $service = new class ($bus) {
            public function __construct(private EventBus $bus)
            {
            }

            #[CommandHandler('collaborator.promoted')]
            public function handle(): void
            {
                $this->bus->publish(new CollaboratorEvent());
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . CollaboratorEvent::class));
        self::assertSame(['inferred' => true, 'via' => 'publish'], $edges[0]->attributes);
    }
}

final class CollaboratorEvent
{
}

interface ExtendedEventBusForFlow extends EventBus
{
}

abstract class EventBusOwnerForFlow
{
    protected EventBus $bus;
}
