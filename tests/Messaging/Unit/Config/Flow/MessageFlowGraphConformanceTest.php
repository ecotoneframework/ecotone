<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Flow;

use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Tracing\InMemorySpanRecorder;
use Ecotone\Api\Tracing\TracingConfiguration;
use Ecotone\Test\Flow\FlowScenario;
use Ecotone\Test\Flow\FlowScenarioCatalogue;
use Ecotone\Test\Flow\MessageFlowGraphVerifier;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageFlowGraphConformanceTest extends TestCase
{
    private const DECIDED_ONLY_AT_RUNTIME = [
        'orchestrator gateway starting the flow' => 'The steps an orchestrator gateway runs are the routing argument its caller passes at runtime, so no static graph can name them.',
    ];

    #[DataProviderExternal(FlowScenarioCatalogue::class, 'dataProvider')]
    public function test_every_recorded_step_of_a_catalogued_flow_is_a_path_in_the_message_flow_graph(FlowScenario $scenario): void
    {
        if (isset(self::DECIDED_ONLY_AT_RUNTIME[$scenario->name])) {
            self::markTestSkipped(self::DECIDED_ONLY_AT_RUNTIME[$scenario->name]);
        }
        $recorder = new InMemorySpanRecorder();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            $scenario->classesToResolve,
            [...$scenario->services, InMemorySpanRecorder::class => $recorder],
            [...$scenario->extensionObjects, TracingConfiguration::createWithDefaults()->withSpanRecorder(InMemorySpanRecorder::class)],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
        foreach (array_keys($scenario->stages) as $index) {
            self::assertNull($scenario->runStage($index, $ecotone));
        }
        self::assertNull(MessageFlowGraphVerifier::mismatchIn($ecotone->getMessageFlowGraph(), $recorder->recordedSpans()));
    }
}
