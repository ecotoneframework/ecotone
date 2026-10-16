<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\ConfigurationVariable;
use Ecotone\Api\Attribute\DecisionBoundary;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Fetch;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\EventSourcing\DecisionModelConcurrencyException;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Messaging\Config\ConfigurationException;
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
final class DecisionBoundaryTest extends TestCase
{
    public function test_decision_boundary_scopes_the_append_condition_without_a_decision_model(): void
    {
        $handler = new RatingHandlerForBoundaryTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, CourseRatedForBoundaryTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->sendCommand(new RateCourseForBoundaryTest('course-1', 5));

        /** @var EventStore $eventStore */
        $eventStore = $ecotone->getGateway(EventStore::class);
        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('course', 'course-1'))->events);
    }

    public function test_non_static_decision_boundary_is_rejected_at_bootstrap(): void
    {
        $this->assertBootstrapRejects(new NonStaticBoundaryHandlerForBoundaryTest(), 'static');
    }

    public function test_decision_boundary_not_declaring_event_criteria_as_its_return_type_is_rejected_at_bootstrap(): void
    {
        $this->assertBootstrapRejects(new WrongReturnTypeBoundaryHandlerForBoundaryTest(), EventCriteria::class);
    }

    public function test_decision_boundary_without_a_command_parameter_is_rejected_at_bootstrap(): void
    {
        $this->assertBootstrapRejects(new ParameterlessBoundaryHandlerForBoundaryTest(), 'first parameter');
    }

    public function test_decision_boundary_matching_no_handler_of_its_class_is_rejected_at_bootstrap(): void
    {
        $this->assertBootstrapRejects(new OrphanBoundaryHandlerForBoundaryTest(), CourseRatedForBoundaryTest::class);
    }

    public function test_two_decision_boundaries_for_the_same_command_are_rejected_at_bootstrap(): void
    {
        $this->assertBootstrapRejects(new DuplicateBoundaryHandlerForBoundaryTest(), 'secondBoundary');
    }

    public function test_a_boundary_scoped_only_by_filter_only_tags_names_the_method_and_the_tags(): void
    {
        $handler = new FilterOnlyBoundaryHandlerForBoundaryTest();

        $ecotone = $this->bootstrapWithRegionAsFilterOnly($handler, [RegionalCourseRatedForBoundaryTest::class]);

        try {
            $ecotone->sendCommand(new RateRegionalCourseForBoundaryTest('eu', 'course-1', 5));
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(FilterOnlyBoundaryHandlerForBoundaryTest::class . '::boundary', $exception->getMessage());
            $this->assertStringContainsString("'region'", $exception->getMessage());
            $this->assertStringContainsString('withFilterOnlyTags', $exception->getMessage());
        }
    }

    public function test_a_boundary_mixing_a_filter_only_tag_with_a_counted_tag_scopes_the_append(): void
    {
        $handler = new MixedFilterOnlyBoundaryHandlerForBoundaryTest();

        $ecotone = $this->bootstrapWithRegionAsFilterOnly($handler, [RegionalCourseRatedForBoundaryTest::class]);

        $ecotone->sendCommand(new RateRegionalCourseForBoundaryTest('eu', 'course-1', 5));

        $this->assertCount(1, $this->eventsMatching($ecotone, EventCriteria::tag('course', 'course-1')));
    }

    private function bootstrapWithRegionAsFilterOnly(object $handler, array $eventClasses): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, ...$eventClasses],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withFilterOnlyTags(['region'])],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_boundary_reading_a_header_conflicts_with_a_competing_write_carrying_the_same_header_value(): void
    {
        $ecotone = $this->bootstrapTenantScopedRating();

        $this->armCompetingWriteWith($ecotone, new TenantCourseRatedForBoundaryTest('acme', 'eu', 'course-9', 1));

        $this->expectException(DecisionModelConcurrencyException::class);

        $ecotone->sendCommand(new RateTenantCourseForBoundaryTest('course-1', 5), metadata: ['tenant' => 'acme']);
    }

    public function test_boundary_reading_a_header_ignores_a_competing_write_carrying_another_header_value(): void
    {
        $ecotone = $this->bootstrapTenantScopedRating();

        $this->armCompetingWriteWith($ecotone, new TenantCourseRatedForBoundaryTest('globex', 'eu', 'course-9', 1));

        $ecotone->sendCommand(new RateTenantCourseForBoundaryTest('course-1', 5), metadata: ['tenant' => 'acme']);

        $this->assertCount(1, $this->eventsMatching($ecotone, EventCriteria::tag('tenant', 'acme')));
    }

    public function test_boundary_conflicts_on_a_tag_value_mapped_by_a_service_resolved_by_type_hint(): void
    {
        $ecotone = $this->bootstrapServiceMappedRating();

        $this->armCompetingWriteWith($ecotone, new CourseRatedForBoundaryTest('course-tier-5', 1));

        $this->expectException(DecisionModelConcurrencyException::class);

        $ecotone->sendCommand(new RateCourseForBoundaryTest('course-1', 5));
    }

    public function test_boundary_ignores_a_competing_write_outside_the_tag_value_mapped_by_a_service(): void
    {
        $ecotone = $this->bootstrapServiceMappedRating();

        $this->armCompetingWriteWith($ecotone, new CourseRatedForBoundaryTest('course-tier-3', 1));

        $ecotone->sendCommand(new RateCourseForBoundaryTest('course-1', 5));

        $this->assertCount(1, $this->eventsMatching($ecotone, EventCriteria::tag('course', 'course-tier-5')));
    }

    public function test_boundary_taking_a_header_a_service_and_a_configuration_variable_conflicts_on_the_configured_region(): void
    {
        $ecotone = $this->bootstrapMixedParameterRating();

        $this->armCompetingWriteWith($ecotone, new RegionAuditedForBoundaryTest('eu'));

        $this->expectException(DecisionModelConcurrencyException::class);

        $ecotone->sendCommand(new RateTenantCourseForBoundaryTest('course-1', 5), metadata: ['tenant' => 'acme']);
    }

    public function test_boundary_taking_a_header_a_service_and_a_configuration_variable_ignores_an_unconfigured_region(): void
    {
        $ecotone = $this->bootstrapMixedParameterRating();

        $this->armCompetingWriteWith($ecotone, new RegionAuditedForBoundaryTest('us'));

        $ecotone->sendCommand(new RateTenantCourseForBoundaryTest('course-1', 5), metadata: ['tenant' => 'acme']);

        $this->assertCount(1, $this->eventsMatching($ecotone, EventCriteria::tag('region', 'eu')));
    }

    public function test_boundary_parameter_that_no_parameter_rule_can_resolve_is_rejected_at_bootstrap(): void
    {
        $this->assertBootstrapRejects(new UnresolvableParameterBoundaryHandlerForBoundaryTest(), '$unresolvable');
    }

    public function test_boundary_taking_a_decision_model_is_rejected_at_bootstrap(): void
    {
        $this->assertBootstrapRejects(new DecisionModelTakingBoundaryHandlerForBoundaryTest(), 'evaluated before the read');
    }

    public function test_boundary_fetching_an_aggregate_is_rejected_at_bootstrap(): void
    {
        $this->assertBootstrapRejects(new FetchingBoundaryHandlerForBoundaryTest(), 'evaluated before the read');
    }

    public function test_boundary_on_a_query_handler_is_rejected_explaining_that_a_query_appends_nothing(): void
    {
        $this->assertBootstrapRejects(new QueryOnlyBoundaryHandlerForBoundaryTest(), 'appends no events');
    }

    private function bootstrapTenantScopedRating(): FlowTestSupport
    {
        $handler = new TenantScopedRatingHandlerForBoundaryTest();

        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, TenantCourseRatedForBoundaryTest::class, CompetingWriteInjectorForBoundaryTest::class],
            containerOrAvailableServices: [$handler, new CompetingWriteInjectorForBoundaryTest()],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    private function bootstrapServiceMappedRating(): FlowTestSupport
    {
        $handler = new ServiceMappedRatingHandlerForBoundaryTest();

        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, CourseRatedForBoundaryTest::class, CourseTagMapperForBoundaryTest::class, CompetingWriteInjectorForBoundaryTest::class],
            containerOrAvailableServices: [$handler, new CourseTagMapperForBoundaryTest(), new CompetingWriteInjectorForBoundaryTest()],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    private function bootstrapMixedParameterRating(): FlowTestSupport
    {
        $handler = new MixedParameterRatingHandlerForBoundaryTest();

        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, TenantCourseRatedForBoundaryTest::class, RegionAuditedForBoundaryTest::class, CourseTagMapperForBoundaryTest::class, CompetingWriteInjectorForBoundaryTest::class],
            containerOrAvailableServices: [$handler, new CourseTagMapperForBoundaryTest(), new CompetingWriteInjectorForBoundaryTest()],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            configurationVariables: ['region' => 'eu'],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    private function armCompetingWriteWith(FlowTestSupport $ecotone, object $event): void
    {
        /** @var CompetingWriteInjectorForBoundaryTest $injector */
        $injector = $ecotone->getServiceFromContainer(CompetingWriteInjectorForBoundaryTest::class);
        $injector->armWith($event);
    }

    /**
     * @return object[]
     */
    private function eventsMatching(FlowTestSupport $ecotone, EventCriteria $criteria): array
    {
        /** @var EventStore $eventStore */
        $eventStore = $ecotone->getGateway(EventStore::class);

        return $eventStore->loadByCriteria($criteria)->events;
    }

    private function assertBootstrapRejects(object $handler, string $expectedMessagePart): void
    {
        try {
            EcotoneLite::bootstrapFlowTesting(
                classesToResolve: [$handler::class, CourseRatedForBoundaryTest::class],
                containerOrAvailableServices: [$handler],
                extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
                enabledModules: [],
                licenceKey: LicenceTesting::VALID_LICENCE,
            );
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString($handler::class, $exception->getMessage());
            $this->assertStringContainsString($expectedMessagePart, $exception->getMessage());
        }
    }
}

final readonly class RateCourseForBoundaryTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
        public int $rating,
    ) {
    }
}

final readonly class CourseRatedForBoundaryTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
        public int $rating,
    ) {
    }
}

final class RatingHandlerForBoundaryTest
{
    #[DecisionBoundary]
    public static function boundary(RateCourseForBoundaryTest $command): EventCriteria
    {
        return EventCriteria::tag('course', $command->courseId);
    }

    #[CommandHandler]
    public function rate(RateCourseForBoundaryTest $command): array
    {
        return [new CourseRatedForBoundaryTest($command->courseId, $command->rating)];
    }
}

final class NonStaticBoundaryHandlerForBoundaryTest
{
    #[DecisionBoundary]
    public function boundary(RateCourseForBoundaryTest $command): EventCriteria
    {
        return EventCriteria::tag('course', $command->courseId);
    }

    #[CommandHandler]
    public function rate(RateCourseForBoundaryTest $command): array
    {
        return [];
    }
}

final class WrongReturnTypeBoundaryHandlerForBoundaryTest
{
    #[DecisionBoundary]
    public static function boundary(RateCourseForBoundaryTest $command): string
    {
        return $command->courseId;
    }

    #[CommandHandler]
    public function rate(RateCourseForBoundaryTest $command): array
    {
        return [];
    }
}

final class ParameterlessBoundaryHandlerForBoundaryTest
{
    #[DecisionBoundary]
    public static function boundary(): EventCriteria
    {
        return EventCriteria::tag('course', 'course-1');
    }

    #[CommandHandler]
    public function rate(RateCourseForBoundaryTest $command): array
    {
        return [];
    }
}

final class OrphanBoundaryHandlerForBoundaryTest
{
    #[DecisionBoundary]
    public static function boundary(CourseRatedForBoundaryTest $event): EventCriteria
    {
        return EventCriteria::tag('course', $event->courseId);
    }

    #[CommandHandler]
    public function rate(RateCourseForBoundaryTest $command): array
    {
        return [];
    }
}

final class DuplicateBoundaryHandlerForBoundaryTest
{
    #[DecisionBoundary]
    public static function firstBoundary(RateCourseForBoundaryTest $command): EventCriteria
    {
        return EventCriteria::tag('course', $command->courseId);
    }

    #[DecisionBoundary]
    public static function secondBoundary(RateCourseForBoundaryTest $command): EventCriteria
    {
        return EventCriteria::tag('course', $command->courseId);
    }

    #[CommandHandler]
    public function rate(RateCourseForBoundaryTest $command): array
    {
        return [];
    }
}


final readonly class RateTenantCourseForBoundaryTest
{
    public function __construct(
        public string $courseId,
        public int $rating,
    ) {
    }
}

final readonly class TenantCourseRatedForBoundaryTest
{
    public function __construct(
        #[EventTag('tenant')] public string $tenant,
        #[EventTag('region')] public string $region,
        #[EventTag('course')] public string $courseId,
        public int $rating,
    ) {
    }
}

final readonly class RegionAuditedForBoundaryTest
{
    public function __construct(
        #[EventTag('region')] public string $region,
    ) {
    }
}

final readonly class CourseRatingQueryForBoundaryTest
{
    public function __construct(
        public string $courseId,
    ) {
    }
}

final class CourseTagMapperForBoundaryTest
{
    public function courseOf(int $rating): string
    {
        return 'course-tier-' . $rating;
    }
}

final class CompetingWriteInjectorForBoundaryTest
{
    private ?object $event = null;

    public function armWith(object $event): void
    {
        $this->event = $event;
    }

    public function maybeInject(EventStore $eventStore): void
    {
        if ($this->event === null) {
            return;
        }

        $event = $this->event;
        $this->event = null;
        $eventStore->appendTo('ecotone_event_stream', [$event]);
    }
}

final class TenantScopedRatingHandlerForBoundaryTest
{
    #[DecisionBoundary]
    public static function boundary(RateTenantCourseForBoundaryTest $command, #[Header('tenant')] string $tenant): EventCriteria
    {
        return EventCriteria::tag('tenant', $tenant);
    }

    #[CommandHandler]
    public function rate(
        RateTenantCourseForBoundaryTest $command,
        #[Header('tenant')] string $tenant,
        #[Reference] CompetingWriteInjectorForBoundaryTest $injector,
        #[Reference] EventStore $eventStore,
    ): array {
        $injector->maybeInject($eventStore);

        return [new TenantCourseRatedForBoundaryTest($tenant, 'eu', $command->courseId, $command->rating)];
    }
}

final class ServiceMappedRatingHandlerForBoundaryTest
{
    #[DecisionBoundary]
    public static function boundary(RateCourseForBoundaryTest $command, CourseTagMapperForBoundaryTest $mapper): EventCriteria
    {
        return EventCriteria::tag('course', $mapper->courseOf($command->rating));
    }

    #[CommandHandler]
    public function rate(
        RateCourseForBoundaryTest $command,
        #[Reference] CourseTagMapperForBoundaryTest $mapper,
        #[Reference] CompetingWriteInjectorForBoundaryTest $injector,
        #[Reference] EventStore $eventStore,
    ): array {
        $injector->maybeInject($eventStore);

        return [new CourseRatedForBoundaryTest($mapper->courseOf($command->rating), $command->rating)];
    }
}

final class MixedParameterRatingHandlerForBoundaryTest
{
    #[DecisionBoundary]
    public static function boundary(
        RateTenantCourseForBoundaryTest $command,
        #[Header('tenant')] string $tenant,
        CourseTagMapperForBoundaryTest $mapper,
        #[ConfigurationVariable('region')] string $region,
    ): EventCriteria {
        return EventCriteria::tag('tenant', $tenant)
            ->andTag('region', $region)
            ->andTag('course', $mapper->courseOf($command->rating));
    }

    #[CommandHandler]
    public function rate(
        RateTenantCourseForBoundaryTest $command,
        #[Header('tenant')] string $tenant,
        #[ConfigurationVariable('region')] string $region,
        #[Reference] CourseTagMapperForBoundaryTest $mapper,
        #[Reference] CompetingWriteInjectorForBoundaryTest $injector,
        #[Reference] EventStore $eventStore,
    ): array {
        $injector->maybeInject($eventStore);

        return [new TenantCourseRatedForBoundaryTest($tenant, $region, $mapper->courseOf($command->rating), $command->rating)];
    }
}

final class UnresolvableParameterBoundaryHandlerForBoundaryTest
{
    #[DecisionBoundary]
    public static function boundary(RateCourseForBoundaryTest $command, string $unresolvable): EventCriteria
    {
        return EventCriteria::tag('course', $unresolvable);
    }

    #[CommandHandler]
    public function rate(RateCourseForBoundaryTest $command): array
    {
        return [];
    }
}

final class QueryOnlyBoundaryHandlerForBoundaryTest
{
    #[DecisionBoundary]
    public static function boundary(CourseRatingQueryForBoundaryTest $query): EventCriteria
    {
        return EventCriteria::tag('course', $query->courseId);
    }

    #[QueryHandler]
    public function rating(CourseRatingQueryForBoundaryTest $query): int
    {
        return 0;
    }
}

#[DecisionModel]
final class CourseRatingsForBoundaryTest
{
    private int $ratings = 0;

    #[Apply]
    public function rated(CourseRatedForBoundaryTest $event): void
    {
        $this->ratings++;
    }

    public function ratings(): int
    {
        return $this->ratings;
    }
}

#[EventSourcingAggregate]
final class CourseForBoundaryTest
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $courseId;

    #[Apply]
    public function applyRated(CourseRatedForBoundaryTest $event): void
    {
        $this->courseId = $event->courseId;
    }
}

final class DecisionModelTakingBoundaryHandlerForBoundaryTest
{
    #[DecisionBoundary]
    public static function boundary(RateCourseForBoundaryTest $command, CourseRatingsForBoundaryTest $ratings): EventCriteria
    {
        return EventCriteria::tag('course', $command->courseId);
    }

    #[CommandHandler]
    public function rate(RateCourseForBoundaryTest $command): array
    {
        return [];
    }
}

final class FetchingBoundaryHandlerForBoundaryTest
{
    #[DecisionBoundary]
    public static function boundary(RateCourseForBoundaryTest $command, #[Fetch('payload.courseId')] CourseForBoundaryTest $course): EventCriteria
    {
        return EventCriteria::tag('course', $command->courseId);
    }

    #[CommandHandler]
    public function rate(RateCourseForBoundaryTest $command): array
    {
        return [];
    }
}

final readonly class RateRegionalCourseForBoundaryTest
{
    public function __construct(
        public string $region,
        public string $courseId,
        public int $rating,
    ) {
    }
}

final readonly class RegionalCourseRatedForBoundaryTest
{
    public function __construct(
        #[EventTag('region')] public string $region,
        #[EventTag('course')] public string $courseId,
        public int $rating,
    ) {
    }
}

final class FilterOnlyBoundaryHandlerForBoundaryTest
{
    #[CommandHandler]
    public function rate(RateRegionalCourseForBoundaryTest $command): array
    {
        return [new RegionalCourseRatedForBoundaryTest($command->region, $command->courseId, $command->rating)];
    }

    #[DecisionBoundary]
    public static function boundary(RateRegionalCourseForBoundaryTest $command): EventCriteria
    {
        return EventCriteria::tag('region', $command->region);
    }
}

final class MixedFilterOnlyBoundaryHandlerForBoundaryTest
{
    #[CommandHandler]
    public function rate(RateRegionalCourseForBoundaryTest $command): array
    {
        return [new RegionalCourseRatedForBoundaryTest($command->region, $command->courseId, $command->rating)];
    }

    #[DecisionBoundary]
    public static function boundary(RateRegionalCourseForBoundaryTest $command): EventCriteria
    {
        return EventCriteria::tag('region', $command->region)->andTag('course', $command->courseId);
    }
}
