<?php

declare(strict_types=1);

namespace Test\Ecotone\EventSourcing\EventStore;

use Ecotone\Api\EventSourcing\AppendCondition;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Messaging\ConcurrencyException;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class InMemoryEventStoreAppendConditionTest extends TestCase
{
    private const STREAM = 'ecotone_event_stream';

    public function test_aggregate_condition_matching_the_current_version_appends_successfully(): void
    {
        $eventStore = $this->bootstrapEcotone()->getGateway(EventStore::class);

        $eventStore->appendTo(self::STREAM, [$this->aggregateEvent('order-1', 1)], AppendCondition::forAggregate('Order', 'order-1', 0));

        $this->assertCount(1, $eventStore->load(self::STREAM));
    }

    public function test_aggregate_condition_with_a_stale_version_raises_concurrency_exception(): void
    {
        $eventStore = $this->bootstrapEcotone()->getGateway(EventStore::class);
        $eventStore->appendTo(self::STREAM, [$this->aggregateEvent('order-1', 1)], AppendCondition::forAggregate('Order', 'order-1', 0));

        $this->expectException(ConcurrencyException::class);

        $eventStore->appendTo(self::STREAM, [$this->aggregateEvent('order-1', 2)], AppendCondition::forAggregate('Order', 'order-1', 0));
    }

    public function test_an_unrelated_aggregate_instance_does_not_conflict(): void
    {
        $eventStore = $this->bootstrapEcotone()->getGateway(EventStore::class);
        $eventStore->appendTo(self::STREAM, [$this->aggregateEvent('order-1', 1)], AppendCondition::forAggregate('Order', 'order-1', 0));

        $eventStore->appendTo(self::STREAM, [$this->aggregateEvent('order-2', 1)], AppendCondition::forAggregate('Order', 'order-2', 0));

        $this->assertCount(2, $eventStore->load(self::STREAM));
    }

    public function test_appending_no_events_under_a_stale_tag_condition_is_a_no_op_like_the_database_store(): void
    {
        $eventStore = $this->bootstrapEcotone(true)->getGateway(EventStore::class);

        $eventStore->appendTo(self::STREAM, [], AppendCondition::fromCapturedVersions([
            ['name' => 'coupon', 'value' => 'SUMMER24', 'expectedVersion' => 5],
        ]));

        $this->assertCount(0, $eventStore->load(self::STREAM));
    }

    public function test_store_without_the_boundary_rejects_a_hand_built_tag_condition(): void
    {
        $eventStore = $this->bootstrapEcotone()->getGateway(EventStore::class);

        $this->expectException(ConfigurationException::class);

        $eventStore->appendTo(self::STREAM, [$this->aggregateEvent('order-1', 1)], AppendCondition::fromCapturedVersions([
            ['name' => 'coupon', 'value' => 'SUMMER24', 'expectedVersion' => 0],
        ]));
    }

    public function test_store_with_the_boundary_still_enforces_the_aggregate_condition_alongside_a_tag_condition(): void
    {
        $eventStore = $this->bootstrapEcotone(true)->getGateway(EventStore::class);
        $eventStore->appendTo(self::STREAM, [$this->aggregateEvent('order-1', 1)], AppendCondition::forAggregate('Order', 'order-1', 0));

        $condition = AppendCondition::forAggregate('Order', 'order-1', 0)
            ->mergeWith(AppendCondition::fromCapturedVersions([
                ['name' => 'coupon', 'value' => 'SUMMER24', 'expectedVersion' => 0],
            ]));

        $this->expectException(ConcurrencyException::class);

        $eventStore->appendTo(self::STREAM, [$this->aggregateEvent('order-1', 2)], $condition);
    }

    private function bootstrapEcotone(bool $dynamicConsistencyBoundaryEnabled = false): FlowTestSupport
    {
        if (! $dynamicConsistencyBoundaryEnabled) {
            return EcotoneLite::bootstrapFlowTesting(enabledModules: []);
        }

        return EcotoneLite::bootstrapFlowTesting(
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    private function aggregateEvent(string $aggregateId, int $version): Event
    {
        return Event::createWithType('OrderPlaced', ['orderId' => $aggregateId], [
            MessageHeaders::EVENT_AGGREGATE_TYPE => 'Order',
            MessageHeaders::EVENT_AGGREGATE_ID => $aggregateId,
            MessageHeaders::EVENT_AGGREGATE_VERSION => $version,
        ]);
    }
}
