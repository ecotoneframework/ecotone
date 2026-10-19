<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Api\Messaging\Flow\MessageFlowGraph;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowSourceNamesTest extends TestCase
{
    public function test_grouped_and_aliased_imports_resolve_each_published_event(): void
    {
        $graph = $this->graphFromSource(<<<'PHP_SOURCE'
            <?php
            namespace FlowChallenge\Grouped;
            use Ecotone\Api\Attribute\CommandHandler;
            use Ecotone\Api\Gateway\EventBus;
            use Test\Ecotone\Messaging\Unit\Config\Flow\{SourceNamesEvent as FirstEvent, OtherSourceNamesEvent};
            return new class () {
                #[CommandHandler('names.grouped')]
                public function handle(EventBus $bus): void
                {
                    $bus->publish(new FirstEvent());
                    $bus->publish(new OtherSourceNamesEvent());
                }
            };
            PHP_SOURCE);

        self::assertEqualsCanonicalizing(['message:' . SourceNamesEvent::class, 'message:' . OtherSourceNamesEvent::class], array_column($graph->edgesOfKind(FlowEdgeKind::EMITS), 'to'));
    }

    public function test_namespace_relative_new_resolves_the_event_in_its_namespace(): void
    {
        $graph = $this->graphFromSource(<<<'PHP_SOURCE'
            <?php
            namespace Test\Ecotone\Messaging\Unit\Config\Flow;
            use Ecotone\Api\Attribute\CommandHandler;
            use Ecotone\Api\Gateway\EventBus;
            return new class () {
                #[CommandHandler('names.relative')]
                public function handle(EventBus $bus): void
                {
                    $bus->publish(new namespace\SourceNamesEvent());
                }
            };
            PHP_SOURCE);

        self::assertSame(['message:' . SourceNamesEvent::class], array_column($graph->edgesOfKind(FlowEdgeKind::EMITS), 'to'));
    }

    public function test_fully_qualified_new_ignores_the_current_namespace(): void
    {
        $graph = $this->graphFromSource(<<<'PHP_SOURCE'
            <?php
            namespace FlowChallenge\Qualified;
            use Ecotone\Api\Attribute\CommandHandler;
            use Ecotone\Api\Gateway\EventBus;
            return new class () {
                #[CommandHandler('names.qualified')]
                public function handle(EventBus $bus): void
                {
                    $bus->publish(new \Test\Ecotone\Messaging\Unit\Config\Flow\SourceNamesEvent());
                }
            };
            PHP_SOURCE);

        self::assertSame(['message:' . SourceNamesEvent::class], array_column($graph->edgesOfKind(FlowEdgeKind::EMITS), 'to'));
    }

    public function test_new_static_resolves_to_the_registered_handler_class(): void
    {
        $graph = EcotoneLite::bootstrapFlowTesting([SourceNamesEvent::class], [new SourceNamesEvent()], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame(['message:' . SourceNamesEvent::class], array_column($graph->edgesOfKind(FlowEdgeKind::EMITS), 'to'));
    }

    public function test_runtime_class_expression_does_not_invent_an_event_class(): void
    {
        $service = new class () {
            #[CommandHandler('names.dynamic')]
            public function handle(array $payload, EventBus $bus): void
            {
                $bus->publish(new ($payload['class'])());
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame([], $graph->edgesOfKind(FlowEdgeKind::EMITS));
    }

    public function test_new_anonymous_event_does_not_invent_a_named_event_class(): void
    {
        $service = new class () {
            #[CommandHandler('names.anonymous')]
            public function handle(EventBus $bus): void
            {
                $bus->publish(new class () {
                });
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertSame([], $graph->edgesOfKind(FlowEdgeKind::EMITS));
    }

    public function test_file_without_namespace_preserves_imported_event_names(): void
    {
        $graph = $this->graphFromSource(<<<'PHP_SOURCE'
            <?php
            use Ecotone\Api\Attribute\CommandHandler;
            use Ecotone\Api\Gateway\EventBus;
            use Test\Ecotone\Messaging\Unit\Config\Flow\SourceNamesEvent;
            return new class () {
                #[CommandHandler('names.global')]
                public function handle(EventBus $bus): void
                {
                    $bus->publish(new SourceNamesEvent());
                }
            };
            PHP_SOURCE);

        self::assertSame(['message:' . SourceNamesEvent::class], array_column($graph->edgesOfKind(FlowEdgeKind::EMITS), 'to'));
    }

    public function test_evaluated_handler_without_readable_source_keeps_declared_routes(): void
    {
        $service = eval(<<<'PHP_SOURCE'
            return new class () {
                #[\Ecotone\Api\Attribute\CommandHandler('names.evaluated')]
                public function handle(\Ecotone\Api\Gateway\EventBus $bus): void
                {
                    $bus->publish(new \Test\Ecotone\Messaging\Unit\Config\Flow\SourceNamesEvent());
                }
            };
            PHP_SOURCE);
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:names.evaluated'));
        self::assertSame([], $graph->edgesOfKind(FlowEdgeKind::EMITS));
    }

    public function test_deleted_included_source_keeps_declared_routes(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'flow-missing-');
        file_put_contents($file, <<<'PHP_SOURCE'
            <?php
            return new class () {
                #[\Ecotone\Api\Attribute\CommandHandler('names.deleted')]
                public function handle(\Ecotone\Api\Gateway\EventBus $bus): void
                {
                    $bus->publish(new \Test\Ecotone\Messaging\Unit\Config\Flow\SourceNamesEvent());
                }
            };
            PHP_SOURCE);
        try {
            $service = require $file;
        } finally {
            unlink($file);
        }
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, 'message:names.deleted'));
        self::assertSame([], $graph->edgesOfKind(FlowEdgeKind::EMITS));
    }

    public function test_bracketed_namespaces_keep_their_own_import_aliases(): void
    {
        $graph = $this->graphFromSource(<<<'PHP_SOURCE'
            <?php
            namespace FlowChallenge\First {
            use Ecotone\Api\Attribute\CommandHandler;
            use Ecotone\Api\Gateway\EventBus;
            use Test\Ecotone\Messaging\Unit\Config\Flow\SourceNamesEvent as PublishedEvent;
            return new class () {
                #[CommandHandler('names.scoped')]
                public function handle(EventBus $bus): void
                {
                    $bus->publish(new PublishedEvent());
                }
            };
            }
            namespace FlowChallenge\Second {
            use Test\Ecotone\Messaging\Unit\Config\Flow\OtherSourceNamesEvent as PublishedEvent;
            }
            PHP_SOURCE);

        self::assertSame(['message:' . SourceNamesEvent::class], array_column($graph->edgesOfKind(FlowEdgeKind::EMITS), 'to'));
    }

    public function test_semicolon_namespaces_keep_their_own_import_aliases(): void
    {
        $graph = $this->graphFromSource(<<<'PHP_SOURCE'
            <?php
            namespace FlowChallenge\First;
            use Ecotone\Api\Attribute\CommandHandler;
            use Ecotone\Api\Gateway\EventBus;
            use Test\Ecotone\Messaging\Unit\Config\Flow\SourceNamesEvent as PublishedEvent;
            return new class () {
                #[CommandHandler('names.scoped')]
                public function handle(EventBus $bus): void
                {
                    $bus->publish(new PublishedEvent());
                }
            };
            namespace FlowChallenge\Second;
            use Test\Ecotone\Messaging\Unit\Config\Flow\OtherSourceNamesEvent as PublishedEvent;
            PHP_SOURCE);

        self::assertSame(['message:' . SourceNamesEvent::class], array_column($graph->edgesOfKind(FlowEdgeKind::EMITS), 'to'));
    }

    private function graphFromSource(string $source): MessageFlowGraph
    {
        $file = tempnam(sys_get_temp_dir(), 'flow-source-');
        file_put_contents($file, $source);
        try {
            $service = require $file;

            return EcotoneLite::bootstrapFlowTesting([$service::class], [$service], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();
        } finally {
            unlink($file);
        }
    }
}

final class SourceNamesEvent
{
    #[CommandHandler('names.static')]
    public static function handle(EventBus $bus): void
    {
        $bus->publish(new static());
    }
}

final class OtherSourceNamesEvent
{
}
