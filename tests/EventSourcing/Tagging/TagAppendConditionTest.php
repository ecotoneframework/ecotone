<?php

declare(strict_types=1);

namespace Test\Ecotone\EventSourcing\Tagging;

use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\EventSourcing\DecisionModelConcurrencyException;
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
final class TagAppendConditionTest extends TestCase
{
    public function test_a_condition_guards_its_tags_even_when_the_appended_events_carry_another_value(): void
    {
        $eventStore = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [CourseChangedForAppendConditionTest::class],
            configuration: ServiceConfiguration::createWithDefaults()->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        )->getGateway(EventStore::class);
        $eventStore->appendTo('ecotone_event_stream', [new CourseChangedForAppendConditionTest('course-1')]);
        $loaded = $eventStore->loadByCriteria(EventCriteria::tag('course', 'course-1'));
        $eventStore->appendTo('ecotone_event_stream', [new CourseChangedForAppendConditionTest('course-1')]);

        $this->expectException(DecisionModelConcurrencyException::class);

        $eventStore->appendTo('ecotone_event_stream', [new CourseChangedForAppendConditionTest('course-2')], $loaded->appendCondition);
    }
}

final readonly class CourseChangedForAppendConditionTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
    ) {
    }
}
