<?php

declare(strict_types=1);

namespace Test\Ecotone\EventSourcing;

use Ecotone\Api\EventSourcing\AppendCondition;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class AppendConditionTest extends TestCase
{
    public function test_empty_condition_has_neither_aggregate_nor_tag_part(): void
    {
        $condition = AppendCondition::empty();

        $this->assertTrue($condition->isEmpty());
        $this->assertFalse($condition->hasAggregateCondition());
        $this->assertFalse($condition->hasTagCondition());
    }

    public function test_for_aggregate_carries_the_aggregate_expectation(): void
    {
        $condition = AppendCondition::forAggregate('Order', 'order-1', 3);

        $this->assertFalse($condition->isEmpty());
        $this->assertTrue($condition->hasAggregateCondition());
        $this->assertFalse($condition->hasTagCondition());
        $this->assertSame('Order', $condition->aggregateType());
        $this->assertSame('order-1', $condition->aggregateId());
        $this->assertSame(3, $condition->expectedAggregateVersion());
    }

    public function test_for_aggregate_accepts_zero_as_the_version_of_a_new_aggregate(): void
    {
        $condition = AppendCondition::forAggregate('Order', 'order-1', 0);

        $this->assertSame(0, $condition->expectedAggregateVersion());
    }

    public function test_from_captured_versions_carries_no_aggregate_expectation(): void
    {
        $condition = AppendCondition::fromCapturedVersions([
            ['name' => 'coupon', 'value' => 'SUMMER24', 'expectedVersion' => 1],
        ]);

        $this->assertTrue($condition->hasTagCondition());
        $this->assertFalse($condition->hasAggregateCondition());
        $this->assertNull($condition->aggregateType());
        $this->assertNull($condition->aggregateId());
        $this->assertNull($condition->expectedAggregateVersion());
    }

    public function test_merging_an_aggregate_condition_with_a_tag_condition_carries_both(): void
    {
        $aggregateCondition = AppendCondition::forAggregate('Order', 'order-1', 0);
        $tagCondition = AppendCondition::fromCapturedVersions([
            ['name' => 'coupon', 'value' => 'SUMMER24', 'expectedVersion' => 1],
        ]);

        $merged = $aggregateCondition->mergeWith($tagCondition);

        $this->assertTrue($merged->hasAggregateCondition());
        $this->assertSame('Order', $merged->aggregateType());
        $this->assertSame('order-1', $merged->aggregateId());
        $this->assertSame(0, $merged->expectedAggregateVersion());
        $this->assertTrue($merged->hasTagCondition());
        $this->assertSame(
            [['name' => 'coupon', 'value' => 'SUMMER24', 'expectedVersion' => 1]],
            $merged->expectedTagVersions(),
        );
    }

    public function test_merging_two_tag_only_conditions_carries_no_aggregate_part(): void
    {
        $first = AppendCondition::fromCapturedVersions([
            ['name' => 'coupon', 'value' => 'SUMMER24', 'expectedVersion' => 1],
        ]);
        $second = AppendCondition::fromCapturedVersions([
            ['name' => 'customer', 'value' => 'alice', 'expectedVersion' => 0],
        ]);

        $merged = $first->mergeWith($second);

        $this->assertFalse($merged->hasAggregateCondition());
        $this->assertCount(2, $merged->expectedTagVersions());
    }
}
