<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Saga;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
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
final class DecisionModelValidationTest extends TestCase
{
    public function test_explicit_tags_naming_a_tag_missing_from_a_handled_event_is_rejected_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [ModelWithMissingTagForValidationTest::class, EventWithoutStudentTagForValidationTest::class],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_apply_method_with_an_interface_typed_parameter_is_rejected(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [ModelWithInterfaceTypedHandlerForValidationTest::class, TaggedEventInterfaceForValidationTest::class],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_decision_model_with_constructor_arguments_is_rejected_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [ModelWithConstructorArgumentsForValidationTest::class, TaggedEventForValidationTest::class],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_command_handler_directly_on_a_decision_model_class_is_rejected_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [ModelWithCommandHandlerForValidationTest::class, TaggedEventForValidationTest::class],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_same_model_class_injected_twice_without_fetch_is_rejected_at_bootstrap(): void
    {
        $handler = new HandlerInjectingSameModelTwiceWithoutFetchForValidationTest();

        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, ModelForValidationTest::class, TaggedEventForValidationTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_decision_model_with_no_handled_events_is_rejected_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [ModelWithNoHandledEventsForValidationTest::class],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_non_nullable_model_param_with_a_null_tag_value_throws_naming_model_and_tag(): void
    {
        $handler = new HandlerRequiringModelForNullTagValueForValidationTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, ModelForValidationTest::class, TaggedEventForValidationTest::class, CommandWithNullTagPropertyForValidationTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $this->expectExceptionMessage('course');

        $ecotone->sendCommand(new CommandWithNullTagPropertyForValidationTest(null));
    }

    public function test_command_without_a_property_for_the_model_tag_is_rejected_at_bootstrap_naming_model_tag_and_command(): void
    {
        $this->assertHandlerRejectedAtBootstrapForUnresolvableTag(new HandlerRequiringModelForValidationTest());
    }

    public function test_command_without_a_property_for_a_nullable_model_tag_is_rejected_at_bootstrap_instead_of_always_injecting_null(): void
    {
        $this->assertHandlerRejectedAtBootstrapForUnresolvableTag(new HandlerAcceptingNullableModelForValidationTest());
    }

    public function test_tag_resolved_from_a_property_named_with_the_underscore_id_convention_is_accepted(): void
    {
        $handler = new HandlerForUnderscoreIdCommandForValidationTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, CountingModelForValidationTest::class, TaggedEventForValidationTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->withEvents([new TaggedEventForValidationTest('course-1')]);
        $ecotone->sendCommand(new CommandWithUnderscoreIdForValidationTest('course-1'));

        $this->assertSame(1, $handler->observedEvents);
    }

    public function test_property_named_with_another_suffix_does_not_resolve_the_tag_by_convention(): void
    {
        $handler = new HandlerForCourseCodeCommandForValidationTest();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("name it 'course', 'courseId' or 'course_id'");

        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, ModelForValidationTest::class, TaggedEventForValidationTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_command_property_holding_several_values_for_a_model_tag_is_rejected_naming_model_tag_and_remedies(): void
    {
        $handler = new HandlerForSeveralCoursesCommandForValidationTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, ModelForValidationTest::class, TaggedEventForValidationTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        try {
            $ecotone->sendCommand(new CommandWithSeveralCoursesForValidationTest(['course-1', 'course-2']));
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(ModelForValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString("'course'", $exception->getMessage());
            $this->assertStringContainsString('#[Fetch]', $exception->getMessage());
            $this->assertStringContainsString('#[DecisionBoundary]', $exception->getMessage());
        }
    }

    public function test_model_scoped_only_by_filter_only_tags_is_rejected_at_bootstrap_naming_model_tag_and_remedies(): void
    {
        try {
            EcotoneLite::bootstrapFlowTesting(
                classesToResolve: [TenantItemsModelForValidationTest::class, TenantItemAddedForValidationTest::class],
                extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withFilterOnlyTags(['tenant'])],
                enabledModules: [],
                licenceKey: LicenceTesting::VALID_LICENCE,
            );
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(TenantItemsModelForValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString("'tenant'", $exception->getMessage());
            $this->assertStringContainsString('tags:', $exception->getMessage());
            $this->assertStringContainsString('withFilterOnlyTags', $exception->getMessage());
        }
    }

    public function test_model_scoped_by_a_filter_only_tag_and_a_counted_tag_folds_only_that_tenants_events(): void
    {
        $handler = new TenantItemCounterForValidationTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, TenantItemModelForValidationTest::class, TenantItemAddedForValidationTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withFilterOnlyTags(['tenant'])],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->withEvents([new TenantItemAddedForValidationTest('acme', 'sku-1'), new TenantItemAddedForValidationTest('globex', 'sku-1')]);

        $this->assertSame(1, $ecotone->sendQueryWithRouting('validation.tenantItemCount', new CountTenantItemForValidationTest('acme', 'sku-1')));
    }

    private function assertHandlerRejectedAtBootstrapForUnresolvableTag(object $handler): void
    {
        try {
            EcotoneLite::bootstrapFlowTesting(
                classesToResolve: [$handler::class, ModelForValidationTest::class, TaggedEventForValidationTest::class, CommandWithoutTagPropertyForValidationTest::class],
                containerOrAvailableServices: [$handler],
                extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
                enabledModules: [],
                licenceKey: LicenceTesting::VALID_LICENCE,
            );
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(ModelForValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString("'course'", $exception->getMessage());
            $this->assertStringContainsString(CommandWithoutTagPropertyForValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString('#[Fetch]', $exception->getMessage());
        }
    }

    public function test_model_handling_an_untagged_event_is_rejected_at_bootstrap_naming_the_event_and_both_remedies(): void
    {
        try {
            EcotoneLite::bootstrapFlowTesting(
                classesToResolve: [ModelHandlingUntaggedEventForValidationTest::class, UntaggedEventForValidationTest::class],
                extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
                enabledModules: [],
                licenceKey: LicenceTesting::VALID_LICENCE,
            );
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(ModelHandlingUntaggedEventForValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString(UntaggedEventForValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString('#[EventTag]', $exception->getMessage());
            $this->assertStringContainsString('#[DecisionModel(tags:', $exception->getMessage());
        }
    }

    public function test_model_handling_a_tagged_event_the_class_scan_did_not_find_is_rejected_naming_the_scan_as_the_cause(): void
    {
        try {
            EcotoneLite::bootstrapFlowTesting(
                classesToResolve: [ModelForValidationTest::class],
                extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
                enabledModules: [],
                licenceKey: LicenceTesting::VALID_LICENCE,
            );
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(TaggedEventForValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString("not found by Ecotone's class scan", $exception->getMessage());
        }
    }

    public function test_model_whose_handled_events_share_no_tag_name_is_rejected_at_bootstrap_naming_every_event_and_both_remedies(): void
    {
        try {
            EcotoneLite::bootstrapFlowTesting(
                classesToResolve: [ModelHandlingDisjointlyTaggedEventsForValidationTest::class, TaggedEventForValidationTest::class, StudentTaggedEventForValidationTest::class],
                extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
                enabledModules: [],
                licenceKey: LicenceTesting::VALID_LICENCE,
            );
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(ModelHandlingDisjointlyTaggedEventsForValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString(TaggedEventForValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString(StudentTaggedEventForValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString('#[EventTag]', $exception->getMessage());
            $this->assertStringContainsString('#[DecisionModel(tags:', $exception->getMessage());
        }
    }

    public function test_decision_model_also_declared_as_an_aggregate_is_rejected_at_bootstrap(): void
    {
        $this->assertModelRejectedAtBootstrapFor(ModelAlsoAggregateForValidationTest::class, EventSourcingAggregate::class);
    }

    public function test_decision_model_also_declared_as_a_saga_is_rejected_at_bootstrap(): void
    {
        $this->assertModelRejectedAtBootstrapFor(ModelAlsoSagaForValidationTest::class, Saga::class);
    }

    private function assertModelRejectedAtBootstrapFor(string $modelClass, string $conflictingAttribute): void
    {
        try {
            EcotoneLite::bootstrapFlowTesting(
                classesToResolve: [$modelClass, TaggedEventForValidationTest::class],
                extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
                enabledModules: [],
                licenceKey: LicenceTesting::VALID_LICENCE,
            );
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString($modelClass, $exception->getMessage());
            $this->assertStringContainsString($conflictingAttribute, $exception->getMessage());
        }
    }
}

final readonly class TaggedEventForValidationTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
    ) {
    }
}

final readonly class EventWithoutStudentTagForValidationTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
    ) {
    }
}

#[DecisionModel(tags: ['course', 'student'])]
final class ModelWithMissingTagForValidationTest
{
    #[Apply]
    public function when(EventWithoutStudentTagForValidationTest $event): void
    {
    }
}

interface TaggedEventInterfaceForValidationTest
{
}

#[DecisionModel]
final class ModelWithInterfaceTypedHandlerForValidationTest
{
    #[Apply]
    public function when(TaggedEventInterfaceForValidationTest $event): void
    {
    }
}

#[DecisionModel]
final class ModelWithConstructorArgumentsForValidationTest
{
    public function __construct(private readonly string $required)
    {
    }

    #[Apply]
    public function when(TaggedEventForValidationTest $event): void
    {
    }
}

#[DecisionModel]
final class ModelForValidationTest
{
    #[Apply]
    public function when(TaggedEventForValidationTest $event): void
    {
    }
}

final readonly class CommandWithoutTagPropertyForValidationTest
{
    public function __construct(
        public string $unrelatedField,
    ) {
    }
}

#[DecisionModel]
final class ModelWithCommandHandlerForValidationTest
{
    #[Apply]
    public function when(TaggedEventForValidationTest $event): void
    {
    }

    #[CommandHandler]
    public function handle(CommandWithoutTagPropertyForValidationTest $command): array
    {
        return [];
    }
}

final class HandlerInjectingSameModelTwiceWithoutFetchForValidationTest
{
    #[CommandHandler]
    public function handle(CommandWithoutTagPropertyForValidationTest $command, ModelForValidationTest $modelA, ModelForValidationTest $modelB): array
    {
        return [];
    }
}

#[DecisionModel]
final class ModelWithNoHandledEventsForValidationTest
{
}

final readonly class CommandWithNullTagPropertyForValidationTest
{
    public function __construct(
        public ?string $courseId,
    ) {
    }
}

final class HandlerRequiringModelForNullTagValueForValidationTest
{
    #[CommandHandler]
    public function handle(CommandWithNullTagPropertyForValidationTest $command, ModelForValidationTest $model): array
    {
        return [];
    }
}

final class HandlerRequiringModelForValidationTest
{
    #[CommandHandler]
    public function handle(CommandWithoutTagPropertyForValidationTest $command, ModelForValidationTest $model): array
    {
        return [];
    }
}

final readonly class UntaggedEventForValidationTest
{
    public function __construct(
        public string $courseId,
    ) {
    }
}

final readonly class StudentTaggedEventForValidationTest
{
    public function __construct(
        #[EventTag('student')] public string $studentId,
    ) {
    }
}

#[DecisionModel]
final class ModelHandlingUntaggedEventForValidationTest
{
    #[Apply]
    public function when(UntaggedEventForValidationTest $event): void
    {
    }
}

#[DecisionModel]
final class ModelHandlingDisjointlyTaggedEventsForValidationTest
{
    #[Apply]
    public function whenCourseTagged(TaggedEventForValidationTest $event): void
    {
    }

    #[Apply]
    public function whenStudentTagged(StudentTaggedEventForValidationTest $event): void
    {
    }
}

#[DecisionModel]
#[EventSourcingAggregate]
final class ModelAlsoAggregateForValidationTest
{
    #[Identifier]
    public string $courseId = '';

    #[Apply]
    public function when(TaggedEventForValidationTest $event): void
    {
    }
}

#[DecisionModel]
#[Saga]
final class ModelAlsoSagaForValidationTest
{
    #[Identifier]
    public string $courseId = '';

    #[Apply]
    public function when(TaggedEventForValidationTest $event): void
    {
    }
}

final class HandlerAcceptingNullableModelForValidationTest
{
    #[CommandHandler]
    public function handle(CommandWithoutTagPropertyForValidationTest $command, ?ModelForValidationTest $model): array
    {
        return [];
    }
}

final readonly class CommandWithUnderscoreIdForValidationTest
{
    public function __construct(
        public string $course_id,
    ) {
    }
}

final class HandlerForUnderscoreIdCommandForValidationTest
{
    public int $observedEvents = 0;

    #[CommandHandler]
    public function handle(CommandWithUnderscoreIdForValidationTest $command, CountingModelForValidationTest $model): array
    {
        $this->observedEvents = $model->count;

        return [];
    }
}

#[DecisionModel]
final class CountingModelForValidationTest
{
    public int $count = 0;

    #[Apply]
    public function when(TaggedEventForValidationTest $event): void
    {
        $this->count++;
    }
}

final readonly class CommandWithCourseCodeForValidationTest
{
    public function __construct(
        public string $courseCode,
    ) {
    }
}

final class HandlerForCourseCodeCommandForValidationTest
{
    #[CommandHandler]
    public function handle(CommandWithCourseCodeForValidationTest $command, ModelForValidationTest $model): array
    {
        return [];
    }
}

final readonly class CommandWithSeveralCoursesForValidationTest
{
    /**
     * @param string[] $courseId
     */
    public function __construct(
        public array $courseId,
    ) {
    }
}

final class HandlerForSeveralCoursesCommandForValidationTest
{
    #[CommandHandler]
    public function handle(CommandWithSeveralCoursesForValidationTest $command, ModelForValidationTest $model): array
    {
        return [];
    }
}

final readonly class TenantItemAddedForValidationTest
{
    public function __construct(
        #[EventTag('tenant')] public string $tenantId,
        #[EventTag('item')] public string $itemId,
    ) {
    }
}

#[DecisionModel(tags: ['tenant'])]
final class TenantItemsModelForValidationTest
{
    #[Apply]
    public function when(TenantItemAddedForValidationTest $event): void
    {
    }
}

#[DecisionModel]
final class TenantItemModelForValidationTest
{
    public int $count = 0;

    #[Apply]
    public function when(TenantItemAddedForValidationTest $event): void
    {
        $this->count++;
    }
}

final readonly class CountTenantItemForValidationTest
{
    public function __construct(
        public string $tenantId,
        public string $itemId,
    ) {
    }
}

final class TenantItemCounterForValidationTest
{
    #[QueryHandler('validation.tenantItemCount')]
    public function count(CountTenantItemForValidationTest $query, TenantItemModelForValidationTest $model): int
    {
        return $model->count;
    }
}
