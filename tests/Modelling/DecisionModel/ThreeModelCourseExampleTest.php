<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\EventSourcingSaga;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
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
final class ThreeModelCourseExampleTest extends TestCase
{
    private const CLASSES = [
        CourseSubscriptionsForThreeModelTest::class,
        CourseCapacityForThreeModelTest::class,
        StudentCoursesForThreeModelTest::class,
        StudentSubscriptionForThreeModelTest::class,
        CourseDefinedForThreeModelTest::class,
        CourseCapacityChangedForThreeModelTest::class,
        StudentSubscribedToCourseForThreeModelTest::class,
        EnrollmentNotificationSagaForThreeModelTest::class,
    ];

    public function test_subscribing_when_course_is_full_throws_business_exception(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->withEvents([new CourseDefinedForThreeModelTest('course-1', 1)]);
        $ecotone->sendCommand(new SubscribeStudentToCourseForThreeModelTest('course-1', 'student-1'));

        $this->expectException(CourseIsFullForThreeModelTest::class);
        $ecotone->sendCommand(new SubscribeStudentToCourseForThreeModelTest('course-1', 'student-2'));
    }

    public function test_subscribing_twice_for_the_same_course_is_a_no_op(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->withEvents([new CourseDefinedForThreeModelTest('course-1', 5)]);
        $ecotone->sendCommand(new SubscribeStudentToCourseForThreeModelTest('course-1', 'student-1'));
        $ecotone->sendCommand(new SubscribeStudentToCourseForThreeModelTest('course-1', 'student-1'));

        $this->assertCount(2, $ecotone->getEventStreamEvents('ecotone_event_stream'));
    }

    public function test_course_capacity_model_is_reused_across_two_handlers_with_different_boundaries(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->withEvents([new CourseDefinedForThreeModelTest('course-1', 3)]);
        $ecotone->sendCommand(new SubscribeStudentToCourseForThreeModelTest('course-1', 'student-1'));
        $ecotone->sendCommand(new ChangeCourseCapacityForThreeModelTest('course-1', 1));

        $this->expectException(CapacityBelowSubscriptionsForThreeModelTest::class);
        $ecotone->sendCommand(new ChangeCourseCapacityForThreeModelTest('course-1', 0));
    }

    public function test_subscribed_event_reaches_event_handler_and_saga(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->withEvents([new CourseDefinedForThreeModelTest('course-1', 5)]);
        $ecotone->sendCommand(new SubscribeStudentToCourseForThreeModelTest('course-1', 'student-1'));

        $this->assertSame(['course-1:student-1'], EventHandlerRecorderForThreeModelTest::$received);
    }

    private function bootstrap()
    {
        EventHandlerRecorderForThreeModelTest::$received = [];

        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: self::CLASSES,
            containerOrAvailableServices: [new CourseSubscriptionsForThreeModelTest(), new EnrollmentNotificationSagaForThreeModelTest()],
            configuration: ServiceConfiguration::createWithDefaults()->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

final readonly class SubscribeStudentToCourseForThreeModelTest
{
    public function __construct(
        public string $courseId,
        public string $studentId,
    ) {
    }
}

final readonly class ChangeCourseCapacityForThreeModelTest
{
    public function __construct(
        public string $courseId,
        public int $capacity,
    ) {
    }
}

final readonly class CourseDefinedForThreeModelTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
        public int $capacity,
    ) {
    }
}

final readonly class CourseCapacityChangedForThreeModelTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
        public int $capacity,
    ) {
    }
}

final readonly class StudentSubscribedToCourseForThreeModelTest
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
final class CourseIsFullForThreeModelTest extends RuntimeException
{
}

/**
 * @internal
 */
final class StudentHasTooManyCoursesForThreeModelTest extends RuntimeException
{
}

/**
 * @internal
 */
final class CapacityBelowSubscriptionsForThreeModelTest extends RuntimeException
{
}

#[DecisionModel]
final class CourseCapacityForThreeModelTest
{
    private int $capacity = 0;
    private int $seatsTaken = 0;

    #[Apply]
    public function defined(CourseDefinedForThreeModelTest $event): void
    {
        $this->capacity = $event->capacity;
    }

    #[Apply]
    public function capacityChanged(CourseCapacityChangedForThreeModelTest $event): void
    {
        $this->capacity = $event->capacity;
    }

    #[Apply]
    public function seatTaken(StudentSubscribedToCourseForThreeModelTest $event): void
    {
        $this->seatsTaken++;
    }

    public function hasFreeSeat(): bool
    {
        return $this->seatsTaken < $this->capacity;
    }

    public function seatsTaken(): int
    {
        return $this->seatsTaken;
    }
}

#[DecisionModel(tags: ['student'])]
final class StudentCoursesForThreeModelTest
{
    private int $courses = 0;

    #[Apply]
    public function joined(StudentSubscribedToCourseForThreeModelTest $event): void
    {
        $this->courses++;
    }

    public function canJoinAnother(): bool
    {
        return $this->courses < 5;
    }
}

#[DecisionModel]
final class StudentSubscriptionForThreeModelTest
{
    private bool $exists = false;

    #[Apply]
    public function subscribed(StudentSubscribedToCourseForThreeModelTest $event): void
    {
        $this->exists = true;
    }

    public function exists(): bool
    {
        return $this->exists;
    }
}

final class EventHandlerRecorderForThreeModelTest
{
    /** @var string[] */
    public static array $received = [];
}

final class CourseSubscriptionsForThreeModelTest
{
    #[CommandHandler]
    public function subscribe(
        SubscribeStudentToCourseForThreeModelTest $command,
        CourseCapacityForThreeModelTest $course,
        StudentCoursesForThreeModelTest $student,
        StudentSubscriptionForThreeModelTest $subscription,
    ): array {
        if ($subscription->exists()) {
            return [];
        }
        if (! $course->hasFreeSeat()) {
            throw new CourseIsFullForThreeModelTest();
        }
        if (! $student->canJoinAnother()) {
            throw new StudentHasTooManyCoursesForThreeModelTest();
        }

        return [new StudentSubscribedToCourseForThreeModelTest($command->courseId, $command->studentId)];
    }

    #[CommandHandler]
    public function changeCapacity(ChangeCourseCapacityForThreeModelTest $command, CourseCapacityForThreeModelTest $course): array
    {
        if ($course->seatsTaken() > $command->capacity) {
            throw new CapacityBelowSubscriptionsForThreeModelTest();
        }

        return [new CourseCapacityChangedForThreeModelTest($command->courseId, $command->capacity)];
    }

    #[EventHandler]
    public function onSubscribed(StudentSubscribedToCourseForThreeModelTest $event): void
    {
        EventHandlerRecorderForThreeModelTest::$received[] = $event->courseId . ':' . $event->studentId;
    }
}

#[EventSourcingSaga]
final class EnrollmentNotificationSagaForThreeModelTest
{
    #[Identifier]
    private string $courseId;

    #[Apply]
    public function onSubscribed(StudentSubscribedToCourseForThreeModelTest $event): void
    {
        $this->courseId = $event->courseId;
    }
}
