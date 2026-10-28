<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Attribute\AggregateType;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\EventSourcing\DecisionModelConcurrencyException;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Api\Tracing\SpanStatus;
use Ecotone\Test\Flow\Fixture\FlowCourseCapacity;
use Ecotone\Test\Flow\Fixture\FlowCourseDefined;
use Ecotone\Test\Flow\Fixture\FlowDefineCourse;
use Ecotone\Test\Flow\Fixture\FlowEnrolStudent;
use Ecotone\Test\Flow\Fixture\FlowEnrolStudentTwice;
use Ecotone\Test\Flow\Fixture\FlowStudentEnrolled;
use Ecotone\Tracing\DynamicConsistencyBoundarySpanAttributes;

/**
 * licence Apache-2.0
 */
final class DynamicConsistencyBoundaryFlowScenarios
{
    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::decisionLoadedBeforeTheHandlerAndAppendedConditionally(),
            self::eventSourcedAggregateSavedAsAConditionalAppend(),
            self::conflictingAppendFailsWithTheConflictRecorded(),
            self::handlerOutsideTheBoundaryRecordsNoBoundarySpans(),
        ];
    }

    private static function decisionLoadedBeforeTheHandlerAndAppendedConditionally(): FlowScenario
    {
        $enrolments = self::enrolments();

        return new FlowScenario('decision models loaded before the handler, its events appended conditionally', self::classes($enrolments), [$enrolments], [DynamicConsistencyBoundaryConfiguration::createWithDefaults()], [
            FlowStage::when(function (FlowTestSupport $ecotone, array $metadata): void {
                $ecotone->withEvents([new FlowCourseDefined('course-1', 5)]);
                $ecotone->sendCommand(new FlowEnrolStudent('course-1', 'student-1'), $metadata);
            })
                ->expect(
                    ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => FlowEnrolStudent::class])->containing(
                        ExpectedSpan::named('Decision Models: class@anonymous::enrol')->ofKind(SpanKind::Internal)->withAttributes([
                            DynamicConsistencyBoundarySpanAttributes::MODELS => FlowCourseCapacity::class,
                            DynamicConsistencyBoundarySpanAttributes::TAGS => 'course:course-1',
                            DynamicConsistencyBoundarySpanAttributes::CAPTURED_VERSIONS => 'course:course-1=1',
                            DynamicConsistencyBoundarySpanAttributes::AGGREGATES => '',
                            DynamicConsistencyBoundarySpanAttributes::EVENTS_FOLDED => 1,
                        ]),
                        ExpectedSpan::named('process class@anonymous::enrol'),
                        self::conditionalAppend(SpanStatus::Ok),
                        ExpectedSpan::named('send event')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => FlowStudentEnrolled::class]),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function eventSourcedAggregateSavedAsAConditionalAppend(): FlowScenario
    {
        $course = new #[EventSourcingAggregate] #[AggregateType('FlowCourse')] class () {
            use WithAggregateVersioning;

            #[Identifier]
            private string $courseId;

            #[CommandHandler]
            public static function define(FlowDefineCourse $command, FlowCourseCapacity $capacity): array
            {
                return [new FlowCourseDefined($command->courseId, $command->capacity)];
            }

            #[Apply]
            public function applyDefined(FlowCourseDefined $event): void
            {
                $this->courseId = $event->courseId;
            }
        };

        return new FlowScenario('event-sourced aggregate saved as a conditional append', [$course::class, FlowDefineCourse::class, FlowCourseCapacity::class, FlowCourseDefined::class, FlowStudentEnrolled::class], [], [DynamicConsistencyBoundaryConfiguration::createWithDefaults()], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommand(new FlowDefineCourse('course-1', 5), $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->containing(
                        ExpectedSpan::named('Decision Models: class@anonymous::define')->ofKind(SpanKind::Internal),
                        ExpectedSpan::named('process class@anonymous::define'),
                        self::conditionalAppend(SpanStatus::Ok),
                        ExpectedSpan::named('send event')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => FlowCourseDefined::class]),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function conflictingAppendFailsWithTheConflictRecorded(): FlowScenario
    {
        $enrolments = self::enrolments();

        return new FlowScenario('conflicting append fails with the conflict recorded', self::classes($enrolments), [$enrolments], [DynamicConsistencyBoundaryConfiguration::createWithDefaults()], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommand(new FlowEnrolStudentTwice('course-1', 'student-1'), $metadata))
                ->failingWith(DecisionModelConcurrencyException::class)
                ->expect(
                    ExpectedSpan::named('send command')->withStatus(SpanStatus::Error)->containing(
                        ExpectedSpan::named('Decision Models: class@anonymous::enrolTwice'),
                        ExpectedSpan::named('process class@anonymous::enrolTwice')->containing(
                            ExpectedSpan::named('send command')->containing(
                                ExpectedSpan::named('Decision Models: class@anonymous::enrol'),
                                ExpectedSpan::named('process class@anonymous::enrol'),
                                self::conditionalAppend(SpanStatus::Ok),
                                ExpectedSpan::named('send event'),
                            ),
                        ),
                        self::conditionalAppend(SpanStatus::Error)->withEventStartingWith(DynamicConsistencyBoundarySpanAttributes::CONFLICT_EVENT_NAME, [
                            DecisionModelConcurrencyException::CONFLICT_TAG_FIELD => 'course:course-1',
                            DecisionModelConcurrencyException::CONFLICT_EXPECTED_VERSION_FIELD => 0,
                            DecisionModelConcurrencyException::CONFLICT_MODEL_FIELD => FlowCourseCapacity::class,
                        ]),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function handlerOutsideTheBoundaryRecordsNoBoundarySpans(): FlowScenario
    {
        $enrolments = new class () {
            #[CommandHandler('enrolment.without_boundary', endpointId: 'enrolment.plain')]
            public function enrol(string $courseId): void
            {
            }
        };

        return new FlowScenario('handler outside the boundary records no boundary spans', [$enrolments::class], [$enrolments], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('enrolment.without_boundary', 'course-1', metadata: $metadata))
                ->expect(ExpectedSpan::named('send command')->containing(ExpectedSpan::named('process class@anonymous::enrol')))
                ->withNoOtherSpans(),
        ]);
    }

    private static function conditionalAppend(SpanStatus $status): ExpectedSpan
    {
        return ExpectedSpan::named('Conditional Append: ecotone_event_stream')->ofKind(SpanKind::Internal)->withStatus($status)->withAttributes([
            DynamicConsistencyBoundarySpanAttributes::TAGS => 'course:course-1',
            DynamicConsistencyBoundarySpanAttributes::EVENTS_APPENDED => 1,
        ]);
    }

    private static function enrolments(): object
    {
        return new class () {
            #[CommandHandler]
            public function enrol(FlowEnrolStudent $command, FlowCourseCapacity $course): array
            {
                return [new FlowStudentEnrolled($command->courseId, $command->studentId)];
            }

            #[CommandHandler]
            public function enrolTwice(FlowEnrolStudentTwice $command, FlowCourseCapacity $course, CommandBus $commandBus): array
            {
                $commandBus->send(new FlowEnrolStudent($command->courseId, $command->studentId));

                return [new FlowStudentEnrolled($command->courseId, $command->studentId)];
            }
        };
    }

    /**
     * @return list<class-string>
     */
    private static function classes(object $enrolments): array
    {
        return [$enrolments::class, FlowCourseCapacity::class, FlowCourseDefined::class, FlowStudentEnrolled::class, FlowEnrolStudent::class, FlowEnrolStudentTwice::class];
    }
}
