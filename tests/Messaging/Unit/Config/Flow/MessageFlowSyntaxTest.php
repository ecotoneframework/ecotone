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
final class MessageFlowSyntaxTest extends TestCase
{
    public function test_trait_handler_keeps_its_emitted_event(): void
    {
        $service = new class () {
            use HandlerTraitForFlow;
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . SyntaxEventForFlow::class));
    }

    public function test_inherited_handler_keeps_its_emitted_event(): void
    {
        $service = new class () extends HandlerParentForFlow {
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle', 'message:' . SyntaxEventForFlow::class));
    }

    public function test_anonymous_classes_with_identical_method_names_keep_separate_emissions(): void
    {
        $first = new class () {
            #[CommandHandler('syntax.first')]
            public function handle(EventBus $bus): void
            {
                $bus->publish(new SyntaxEventForFlow());
            }
        };
        $second = new class () {
            #[CommandHandler('syntax.second')]
            public function handle(EventBus $bus): void
            {
                $bus->publish(new OtherSyntaxEventForFlow());
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$first::class, $second::class], [$first, $second], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame(['message:' . SyntaxEventForFlow::class], array_column($graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $first::class . '::handle'), 'to'));
        self::assertSame(['message:' . OtherSyntaxEventForFlow::class], array_column($graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $second::class . '::handle'), 'to'));
    }

    public function test_closure_use_preserves_imports_and_captured_event_publication(): void
    {
        $service = new class () {
            #[CommandHandler('syntax.closure')]
            public function handle(EventBus $bus): void
            {
                $event = new SyntaxEventForFlow();
                $publish = function () use ($event, $bus): void {
                    $bus->publish($event);
                };
                $publish();
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle'));
        self::assertSame('message:' . SyntaxEventForFlow::class, $edges[0]->to);
        self::assertSame('publish', $edges[0]->attributes['via']);
    }

    public function test_nested_parentheses_and_code_like_strings_keep_the_outer_publication(): void
    {
        $service = new class () {
            #[CommandHandler('syntax.parentheses')]
            public function handle(EventBus $bus): void
            {
                $bus->publish(new SyntaxEventForFlow(str_replace('(', ')', 'new -> (')));
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $edges = $graph->edgesOfKind(FlowEdgeKind::EMITS, 'handler:' . $service::class . '::handle'));
        self::assertSame('message:' . SyntaxEventForFlow::class, $edges[0]->to);
        self::assertSame('publish', $edges[0]->attributes['via']);
    }
    public function test_inherited_self_and_static_construction_keep_their_distinct_event_classes(): void
    {
        $service = new class () extends SelfEventForFlow {
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertEqualsCanonicalizing(['message:' . SelfEventForFlow::class, 'message:' . $service::class], array_column($graph->edgesOfKind(FlowEdgeKind::EMITS), 'to'));
    }

}

trait HandlerTraitForFlow
{
    #[CommandHandler('syntax.trait')]
    public function handle(EventBus $bus): void
    {
        $bus->publish(new SyntaxEventForFlow());
    }
}

abstract class HandlerParentForFlow
{
    #[CommandHandler('syntax.parent')]
    public function handle(EventBus $bus): void
    {
        $bus->publish(new SyntaxEventForFlow());
    }
}

final class SyntaxEventForFlow
{
    public function __construct(public string $text = '')
    {
    }
}

final class OtherSyntaxEventForFlow
{
}

class SelfEventForFlow
{
    #[CommandHandler('syntax.self')]
    public function handle(EventBus $bus): void
    {
        $bus->publish(new self());
        $bus->publish(new static());
    }
}
