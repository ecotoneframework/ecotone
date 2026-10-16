<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\InstantRetry;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\EventSourcing\DecisionModelConcurrencyException;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\ExtensionObject\InstantRetryConfiguration;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Modelling\Config\DatabaseTransaction\TransactionStatusTracker;
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
final class DecisionModelRetryTest extends TestCase
{
    public function test_without_retry_configured_the_conflict_surfaces_with_tag_and_versions_in_the_message(): void
    {
        $ecotone = $this->bootstrap();

        $ecotone->withEvents([new CourseDefinedForRetryTest('course-1', 5)]);

        $injector = $ecotone->getServiceFromContainer(CompetingWriteInjectorForRetryTest::class);
        $injector->arm();

        try {
            $ecotone->sendCommand(new SubscribeToCourseForRetryTest('course-1', 'student-1'));
            $this->fail('Expected a DecisionModelConcurrencyException');
        } catch (DecisionModelConcurrencyException $exception) {
            $this->assertStringContainsString('course', $exception->getMessage());
            $this->assertStringContainsString('course-1', $exception->getMessage());
        }
    }

    public function test_instant_retry_configuration_makes_an_injected_conflict_succeed_on_the_second_pass(): void
    {
        $ecotone = $this->bootstrap(
            InstantRetryConfiguration::createWithDefaults()
                ->withCommandBusRetry(true, 3, [DecisionModelConcurrencyException::class])
        );

        $ecotone->withEvents([new CourseDefinedForRetryTest('course-1', 5)]);

        $injector = $ecotone->getServiceFromContainer(CompetingWriteInjectorForRetryTest::class);
        $injector->arm();

        $ecotone->sendCommand(new SubscribeToCourseForRetryTest('course-1', 'student-1'));

        /** @var EventStore $eventStore */
        $eventStore = $ecotone->getGateway(EventStore::class);
        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('student', 'student-1'))->events);
    }

    public function test_retry_exhaustion_surfaces_the_concurrency_exception_to_the_caller(): void
    {
        $handler = new SubscribeHandlerForExhaustionRetryTest();
        $injector = new AlwaysConflictingWriteInjectorForRetryTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, CourseForRetryTest::class, CourseDefinedForRetryTest::class, StudentSubscribedForRetryTest::class, AlwaysConflictingWriteInjectorForRetryTest::class],
            containerOrAvailableServices: [$handler, $injector],
            extensionObjects: [
                DynamicConsistencyBoundaryConfiguration::createWithDefaults(),
                InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true, 2, [DecisionModelConcurrencyException::class]),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->withEvents([new CourseDefinedForRetryTest('course-1', 5)]);

        $this->expectException(DecisionModelConcurrencyException::class);

        try {
            $ecotone->sendCommand(new SubscribeToCourseForRetryTest('course-1', 'student-1'));
        } finally {
            $this->assertSame(3, $injector->attempts, 'Expected the initial attempt plus 2 retries, all conflicting.');
        }
    }

    public function test_no_retry_happens_inside_an_outer_database_transaction(): void
    {
        $ecotone = $this->bootstrap(
            InstantRetryConfiguration::createWithDefaults()
                ->withCommandBusRetry(true, 3, [DecisionModelConcurrencyException::class])
        );

        $ecotone->withEvents([new CourseDefinedForRetryTest('course-1', 5)]);

        $injector = $ecotone->getServiceFromContainer(CompetingWriteInjectorForRetryTest::class);
        $injector->arm();

        /** @var TransactionStatusTracker $transactionStatusTracker */
        $transactionStatusTracker = $ecotone->getServiceFromContainer(TransactionStatusTracker::class);
        $transactionStatusTracker->markAsInsideTransaction();

        try {
            $this->expectException(DecisionModelConcurrencyException::class);
            $ecotone->sendCommand(new SubscribeToCourseForRetryTest('course-1', 'student-1'));
        } finally {
            $transactionStatusTracker->markAsOutsideTransaction();
        }
    }

    public function test_enterprise_instant_retry_on_a_command_bus_interface_works_the_same_as_the_configuration(): void
    {
        $handler = new SubscribeHandlerForRetryTest();
        $injector = new CompetingWriteInjectorForRetryTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [
                $handler::class,
                CourseForRetryTest::class,
                CourseDefinedForRetryTest::class,
                StudentSubscribedForRetryTest::class,
                CompetingWriteInjectorForRetryTest::class,
                RetryCommandBusForRetryTest::class,
            ],
            containerOrAvailableServices: [$handler, $injector],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->withEvents([new CourseDefinedForRetryTest('course-1', 5)]);

        $injector->arm();

        $ecotone->getGateway(RetryCommandBusForRetryTest::class)->send(new SubscribeToCourseForRetryTest('course-1', 'student-1'));

        /** @var EventStore $eventStore */
        $eventStore = $ecotone->getGateway(EventStore::class);
        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('student', 'student-1'))->events);
    }

    private function bootstrap(?InstantRetryConfiguration $retryConfiguration = null)
    {
        $handler = new SubscribeHandlerForRetryTest();
        $injector = new CompetingWriteInjectorForRetryTest();

        $extensionObjects = [DynamicConsistencyBoundaryConfiguration::createWithDefaults()];
        if ($retryConfiguration !== null) {
            $extensionObjects[] = $retryConfiguration;
        }

        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, CourseForRetryTest::class, CourseDefinedForRetryTest::class, StudentSubscribedForRetryTest::class, CompetingWriteInjectorForRetryTest::class],
            containerOrAvailableServices: [$handler, $injector],
            extensionObjects: $extensionObjects,
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

#[InstantRetry(retryTimes: 3, exceptions: [DecisionModelConcurrencyException::class])]
interface RetryCommandBusForRetryTest extends CommandBus
{
}

final readonly class SubscribeToCourseForRetryTest
{
    public function __construct(
        public string $courseId,
        public string $studentId,
    ) {
    }
}

final readonly class CourseDefinedForRetryTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
        public int $capacity,
    ) {
    }
}

final readonly class StudentSubscribedForRetryTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
        #[EventTag('student')] public string $studentId,
    ) {
    }
}

#[DecisionModel]
final class CourseForRetryTest
{
    private int $capacity = 0;
    private int $seatsTaken = 0;

    #[Apply]
    public function defined(CourseDefinedForRetryTest $event): void
    {
        $this->capacity = $event->capacity;
    }

    #[Apply]
    public function seatTaken(StudentSubscribedForRetryTest $event): void
    {
        $this->seatsTaken++;
    }

    public function hasFreeSeat(): bool
    {
        return $this->seatsTaken < $this->capacity;
    }
}

final class CompetingWriteInjectorForRetryTest
{
    private bool $armed = false;

    public function arm(): void
    {
        $this->armed = true;
    }

    public function maybeInject(EventStore $eventStore): void
    {
        if (! $this->armed) {
            return;
        }

        $this->armed = false;
        $eventStore->appendTo('ecotone_event_stream', [
            new StudentSubscribedForRetryTest('course-1', 'interloper'),
        ]);
    }
}

final class SubscribeHandlerForRetryTest
{
    #[CommandHandler]
    public function subscribe(
        SubscribeToCourseForRetryTest $command,
        CourseForRetryTest $course,
        #[Reference] CompetingWriteInjectorForRetryTest $injector,
        #[Reference] EventStore $eventStore,
    ): array {
        $injector->maybeInject($eventStore);

        return [new StudentSubscribedForRetryTest($command->courseId, $command->studentId)];
    }
}

final class AlwaysConflictingWriteInjectorForRetryTest
{
    public int $attempts = 0;

    public function maybeInject(EventStore $eventStore): void
    {
        $this->attempts++;
        $eventStore->appendTo('ecotone_event_stream', [
            new StudentSubscribedForRetryTest('course-1', 'interloper-' . $this->attempts),
        ]);
    }
}

final class SubscribeHandlerForExhaustionRetryTest
{
    #[CommandHandler]
    public function subscribe(
        SubscribeToCourseForRetryTest $command,
        CourseForRetryTest $course,
        #[Reference] AlwaysConflictingWriteInjectorForRetryTest $injector,
        #[Reference] EventStore $eventStore,
    ): array {
        $injector->maybeInject($eventStore);

        return [new StudentSubscribedForRetryTest($command->courseId, $command->studentId)];
    }
}
