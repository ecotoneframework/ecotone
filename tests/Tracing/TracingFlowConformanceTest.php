<?php

declare(strict_types=1);

namespace Test\Ecotone\Tracing;

use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Tracing\InMemorySpanRecorder;
use Ecotone\Test\Flow\FlowScenario;
use Ecotone\Test\Flow\FlowScenarioCatalogue;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\Attributes\DataProviderExternal;

/**
 * licence Apache-2.0
 * @internal
 */
final class TracingFlowConformanceTest extends TracingTestCase
{
    #[DataProviderExternal(FlowScenarioCatalogue::class, 'dataProvider')]
    public function test_every_catalogued_flow_is_recorded_as_its_expected_spans(FlowScenario $scenario): void
    {
        $recorder = new InMemorySpanRecorder();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            $scenario->classesToResolve,
            [...$scenario->services, ...self::recordingSpansInto($recorder)],
            [...$scenario->extensionObjects, self::tracingConfiguration()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        foreach (array_keys($scenario->stages) as $index) {
            self::assertNull($scenario->runStage($index, $ecotone));

            self::assertNull($scenario->mismatchAfterStage($index, $recorder->recordedSpans()));
        }
    }
}
