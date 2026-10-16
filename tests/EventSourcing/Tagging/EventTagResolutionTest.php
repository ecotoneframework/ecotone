<?php

declare(strict_types=1);

namespace Test\Ecotone\EventSourcing\Tagging;

use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
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
final class EventTagResolutionTest extends TestCase
{
    public function test_plain_property_is_resolved_as_a_tag(): void
    {
        $eventStore = $this->bootstrapEventStore([EventTaggedOnPlainProperty::class]);

        $eventStore->appendTo('ecotone_event_stream', [new EventTaggedOnPlainProperty('course-1')]);

        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('course', 'course-1'))->events);
    }

    public function test_method_computed_value_is_resolved_as_a_tag(): void
    {
        $eventStore = $this->bootstrapEventStore([EventTaggedOnMethod::class]);

        $eventStore->appendTo('ecotone_event_stream', [new EventTaggedOnMethod('summer24')]);

        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('customerEmail', 'SUMMER24-HASH'))->events);
    }

    public function test_class_level_literal_value_is_resolved_as_a_tag(): void
    {
        $eventStore = $this->bootstrapEventStore([EventTaggedOnClass::class]);

        $eventStore->appendTo('ecotone_event_stream', [new EventTaggedOnClass(1)]);

        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('invoiceSequence', 'default'))->events);
    }

    public function test_repeated_key_on_different_properties_produces_two_tag_rows(): void
    {
        $eventStore = $this->bootstrapEventStore([MoneyTransferredForResolutionTest::class]);

        $eventStore->appendTo('ecotone_event_stream', [new MoneyTransferredForResolutionTest('acc-1', 'acc-2')]);

        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('account', 'acc-1'))->events);
        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('account', 'acc-2'))->events);
    }

    public function test_array_value_produces_one_tag_row_per_element(): void
    {
        $eventStore = $this->bootstrapEventStore([SeatsReservedForResolutionTest::class]);

        $eventStore->appendTo('ecotone_event_stream', [new SeatsReservedForResolutionTest(['seat-1', 'seat-2'])]);

        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('seat', 'seat-1'))->events);
        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('seat', 'seat-2'))->events);
    }

    public function test_null_value_is_skipped_and_produces_no_tag_row(): void
    {
        $eventStore = $this->bootstrapEventStore([OrderPlacedForResolutionTest::class]);

        $eventStore->appendTo('ecotone_event_stream', [new OrderPlacedForResolutionTest('customer-1', null)]);

        $this->assertCount(0, $eventStore->loadByCriteria(EventCriteria::tag('coupon', ''))->events);
        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('customer', 'customer-1'))->events);
    }

    public function test_empty_tag_value_is_rejected(): void
    {
        $eventStore = $this->bootstrapEventStore([EventTaggedOnPlainProperty::class]);

        $this->expectException(ConfigurationException::class);

        $eventStore->appendTo('ecotone_event_stream', [new EventTaggedOnPlainProperty('')]);
    }

    public function test_too_long_tag_value_is_rejected(): void
    {
        $eventStore = $this->bootstrapEventStore([EventTaggedOnPlainProperty::class]);

        $this->expectException(ConfigurationException::class);

        $eventStore->appendTo('ecotone_event_stream', [new EventTaggedOnPlainProperty(str_repeat('a', 256))]);
    }

    public function test_trailing_whitespace_tag_value_is_rejected(): void
    {
        $eventStore = $this->bootstrapEventStore([EventTaggedOnPlainProperty::class]);

        $this->expectException(ConfigurationException::class);

        $eventStore->appendTo('ecotone_event_stream', [new EventTaggedOnPlainProperty('course-1 ')]);
    }

    public function test_non_scalar_typed_property_is_rejected_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);

        $this->bootstrapEventStore([EventTaggedOnNonScalarProperty::class]);
    }

    public function test_tag_value_of_255_multibyte_characters_is_accepted(): void
    {
        $eventStore = $this->bootstrapEventStore([EventTaggedOnPlainProperty::class]);
        $value = str_repeat('ż', 255);

        $eventStore->appendTo('ecotone_event_stream', [new EventTaggedOnPlainProperty($value)]);

        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('course', $value))->events);
    }

    public function test_tag_value_of_256_multibyte_characters_is_rejected_counting_characters(): void
    {
        $eventStore = $this->bootstrapEventStore([EventTaggedOnPlainProperty::class]);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('got 256');

        $eventStore->appendTo('ecotone_event_stream', [new EventTaggedOnPlainProperty(str_repeat('ż', 256))]);
    }

    public function test_tag_value_containing_a_nul_byte_is_rejected(): void
    {
        $eventStore = $this->bootstrapEventStore([EventTaggedOnPlainProperty::class]);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('NUL');

        $eventStore->appendTo('ecotone_event_stream', [new EventTaggedOnPlainProperty("course\0-1")]);
    }

    public function test_tag_value_that_is_not_valid_utf8_is_rejected(): void
    {
        $eventStore = $this->bootstrapEventStore([EventTaggedOnPlainProperty::class]);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('UTF-8');

        $eventStore->appendTo('ecotone_event_stream', [new EventTaggedOnPlainProperty("course-\xff")]);
    }

    public function test_tag_name_longer_than_100_characters_is_rejected_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('100');

        $this->bootstrapEventStore([EventWithTooLongTagName::class]);
    }

    public function test_empty_tag_name_is_rejected_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(EventWithEmptyTagName::class);

        $this->bootstrapEventStore([EventWithEmptyTagName::class]);
    }

    public function test_subclass_of_a_tagged_event_carries_the_tags_it_inherits(): void
    {
        $eventStore = $this->bootstrapEventStore([ParentEventTaggedForResolutionTest::class]);

        $eventStore->appendTo('ecotone_event_stream', [new ChildOfTaggedEventForResolutionTest('course-1')]);

        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('course', 'course-1'))->events);
    }

    /**
     * @param class-string[] $classesToResolve
     */
    private function bootstrapEventStore(array $classesToResolve): EventStore
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: $classesToResolve,
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        return $ecotone->getGateway(EventStore::class);
    }
}

final class EventTaggedOnPlainProperty
{
    #[EventTag('course')]
    public string $courseId;

    public function __construct(string $courseId)
    {
        $this->courseId = $courseId;
    }
}

final readonly class EventTaggedOnMethod
{
    public function __construct(
        public string $couponCode,
    ) {
    }

    #[EventTag('customerEmail')]
    public function customerEmailHash(): string
    {
        return strtoupper($this->couponCode) . '-HASH';
    }
}

#[EventTag('invoiceSequence', value: 'default')]
final readonly class EventTaggedOnClass
{
    public function __construct(
        public int $number,
    ) {
    }
}

final readonly class MoneyTransferredForResolutionTest
{
    public function __construct(
        #[EventTag('account')] public string $fromAccountId,
        #[EventTag('account')] public string $toAccountId,
    ) {
    }
}

final readonly class SeatsReservedForResolutionTest
{
    /**
     * @param string[] $seatIds
     */
    public function __construct(
        #[EventTag('seat')] public array $seatIds,
    ) {
    }
}

final readonly class OrderPlacedForResolutionTest
{
    public function __construct(
        #[EventTag('customer')] public string $customerId,
        #[EventTag('coupon')] public ?string $couponCode,
    ) {
    }
}

final readonly class EventTaggedOnNonScalarProperty
{
    public function __construct(
        #[EventTag('course')] public object $course,
    ) {
    }
}

final readonly class EventWithTooLongTagName
{
    public function __construct(
        #[EventTag('a_tag_name_that_goes_on_and_on_well_past_the_one_hundred_characters_the_tag_name_columns_can_hold_xxx')] public string $id,
    ) {
    }
}

final readonly class EventWithEmptyTagName
{
    public function __construct(
        #[EventTag('')] public string $id,
    ) {
    }
}

class ParentEventTaggedForResolutionTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
    ) {
    }
}

/**
 * @internal
 */
final class ChildOfTaggedEventForResolutionTest extends ParentEventTaggedForResolutionTest
{
}
