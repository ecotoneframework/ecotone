<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Attribute;
use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Before;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Api\Messaging\Flow\FlowNodeKind;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowInterceptorTest extends TestCase
{
    public function test_interceptor_is_reached_from_the_handlers_its_pointcut_cuts_and_shows_what_it_sends(): void
    {
        $orders = new class () {
            #[AuditedForFlow]
            #[CommandHandler('order.place')]
            public function place(string $orderId): void
            {
            }

            #[CommandHandler('audit.record')]
            public function record(string $orderId): void
            {
            }
        };
        $auditing = new class () {
            #[Before(pointcut: AuditedForFlow::class)]
            public function audit(string $orderId, #[Reference] CommandBus $commandBus): void
            {
                $commandBus->sendWithRouting('audit.record', $orderId);
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$orders::class, $auditing::class], [$orders, $auditing], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $interceptor = 'interceptor:' . $auditing::class . '::audit';
        self::assertSame(FlowNodeKind::INTERCEPTOR, $graph->node($interceptor)->kind);
        self::assertSame('before', $graph->node($interceptor)->attributes['position']);
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::INTERCEPTED_BY, 'handler:' . $orders::class . '::place', $interceptor));
        self::assertCount(0, $graph->edgesOfKind(FlowEdgeKind::INTERCEPTED_BY, 'handler:' . $orders::class . '::record', $interceptor));
        self::assertCount(1, $emits = $graph->edgesOfKind(FlowEdgeKind::EMITS, $interceptor, 'message:audit.record'));
        self::assertSame(['inferred' => true, 'via' => 'sendWithRouting'], $emits[0]->attributes);
    }

    public function test_interceptor_cutting_a_handler_attribute_reaches_every_handler_of_that_kind_only(): void
    {
        $service = new class () {
            #[CommandHandler('order.place')]
            public function place(string $orderId): void
            {
            }

            #[EventHandler('order.placed')]
            public function whenPlaced(string $orderId): void
            {
            }
        };
        $timing = new class () {
            #[Around(pointcut: CommandHandler::class)]
            public function time(MethodInvocation $invocation): mixed
            {
                return $invocation->proceed();
            }
        };
        $graph = EcotoneLite::bootstrapFlowTesting([$service::class, $timing::class], [$service, $timing], enabledModules: [], licenceKey: LicenceTesting::VALID_LICENCE)->getMessageFlowGraph();

        $interceptor = 'interceptor:' . $timing::class . '::time';
        self::assertSame('around', $graph->node($interceptor)->attributes['position']);
        self::assertCount(1, $graph->edgesOfKind(FlowEdgeKind::INTERCEPTED_BY, 'handler:' . $service::class . '::place', $interceptor));
        self::assertCount(0, $graph->edgesOfKind(FlowEdgeKind::INTERCEPTED_BY, 'handler:' . $service::class . '::whenPlaced', $interceptor));
    }
}

#[Attribute(Attribute::TARGET_METHOD)]
final class AuditedForFlow
{
}
