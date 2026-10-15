<?php

declare(strict_types=1);

namespace Test\Ecotone\EventSourcing\Tagging;

use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 *
 * @internal
 */
final class EventStoreGatewayLoadByCriteriaTest extends TestCase
{
    public function test_loading_events_by_criteria_through_the_gateway_returns_the_event_that_carries_it(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [StudentSubscribedForGatewayTest::class],
            configuration: ServiceConfiguration::createWithDefaults()->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $eventStore = $ecotone->getGateway(EventStore::class);

        $eventStore->appendTo('ecotone_event_stream', [
            new StudentSubscribedForGatewayTest('course-1', 'student-1'),
        ]);

        $loadedEvents = $eventStore->loadByCriteria(EventCriteria::tag('course', 'course-1'));

        $this->assertCount(1, $loadedEvents->events);
        $this->assertEquals(
            new StudentSubscribedForGatewayTest('course-1', 'student-1'),
            $loadedEvents->events[0]->getPayload(),
        );
    }

    public function test_or_criteria_through_the_gateway_returns_events_matching_either_criterion(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [StudentSubscribedForGatewayTest::class],
            configuration: ServiceConfiguration::createWithDefaults()->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $eventStore = $ecotone->getGateway(EventStore::class);

        $eventStore->appendTo('ecotone_event_stream', [
            new StudentSubscribedForGatewayTest('course-1', 'student-1'),
            new StudentSubscribedForGatewayTest('course-2', 'student-2'),
        ]);

        $loadedEvents = $eventStore->loadByCriteria(
            EventCriteria::tag('course', 'course-1')->or(EventCriteria::tag('course', 'course-2')),
        );

        $this->assertCount(2, $loadedEvents->events);
    }
}

final readonly class StudentSubscribedForGatewayTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
        #[EventTag('student')] public string $studentId,
    ) {
    }
}
