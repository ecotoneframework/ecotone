<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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
final class DecisionModelBasicTest extends TestCase
{
    public function test_command_handler_injects_a_decision_model_folded_from_tagged_events(): void
    {
        $courseSubscriptions = new class () {
            #[CommandHandler]
            public function subscribe(SubscribeStudentToCourse $command, CourseCapacityForBasicTest $course): array
            {
                if (! $course->hasFreeSeat()) {
                    throw new CourseIsFullForBasicTest();
                }

                return [new StudentSubscribedToCourseForBasicTest($command->courseId, $command->studentId)];
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$courseSubscriptions::class, CourseCapacityForBasicTest::class, CourseDefinedForBasicTest::class, StudentSubscribedToCourseForBasicTest::class],
            containerOrAvailableServices: [$courseSubscriptions],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->withEvents([new CourseDefinedForBasicTest('course-1', 1)]);

        $ecotone->sendCommand(new SubscribeStudentToCourse('course-1', 'student-1'));

        $this->expectException(CourseIsFullForBasicTest::class);
        $ecotone->sendCommand(new SubscribeStudentToCourse('course-1', 'student-2'));
    }
}

final readonly class SubscribeStudentToCourse
{
    public function __construct(
        public string $courseId,
        public string $studentId,
    ) {
    }
}

final readonly class CourseDefinedForBasicTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
        public int $capacity,
    ) {
    }
}

final readonly class StudentSubscribedToCourseForBasicTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
        #[EventTag('student')] public string $studentId,
    ) {
    }
}

/**
 * @internal
 */
final class CourseIsFullForBasicTest extends RuntimeException
{
}

#[DecisionModel]
final class CourseCapacityForBasicTest
{
    private int $capacity = 0;
    private int $seatsTaken = 0;

    #[Apply]
    public function defined(CourseDefinedForBasicTest $event): void
    {
        $this->capacity = $event->capacity;
    }

    #[Apply]
    public function seatTaken(StudentSubscribedToCourseForBasicTest $event): void
    {
        $this->seatsTaken++;
    }

    public function hasFreeSeat(): bool
    {
        return $this->seatsTaken < $this->capacity;
    }
}
