<?php

declare(strict_types=1);

namespace Test\Ecotone\EventSourcing\EventStore\AppendStrategy;

use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\EventSourcing\AppendCondition;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\EventSourcing\EventCriteria;
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
final class AppendStrategyTest extends TestCase
{
    private const STREAM = 'ecotone_event_stream';

    public function test_without_the_boundary_appends_unconditionally_when_no_condition_is_given(): void
    {
        $eventStore = $this->bootstrapEcotone()->getGateway(EventStore::class);

        $eventStore->appendTo(self::STREAM, [$this->aggregateEvent('order-1', 1)]);

        $this->assertCount(1, $eventStore->load(self::STREAM));
    }

    public function test_without_the_boundary_honours_an_aggregate_only_condition(): void
    {
        $eventStore = $this->bootstrapEcotone()->getGateway(EventStore::class);
        $eventStore->appendTo(self::STREAM, [$this->aggregateEvent('order-1', 1)], AppendCondition::forAggregate('Order', 'order-1', 0));

        $this->expectException(ConcurrencyException::class);

        $eventStore->appendTo(self::STREAM, [$this->aggregateEvent('order-1', 2)], AppendCondition::forAggregate('Order', 'order-1', 0));
    }

    public function test_without_the_boundary_rejects_a_condition_carrying_a_tag_part(): void
    {
        $eventStore = $this->bootstrapEcotone()->getGateway(EventStore::class);

        $this->expectException(ConfigurationException::class);

        $eventStore->appendTo(self::STREAM, [$this->aggregateEvent('order-1', 1)], AppendCondition::fromCapturedVersions([
            ['name' => 'coupon', 'value' => 'SUMMER24', 'expectedVersion' => 1],
        ]));
    }

    public function test_with_the_boundary_indexes_a_tagged_event_by_its_tag_even_without_an_explicit_condition(): void
    {
        $eventStore = $this->bootstrapEcotone(true, [CouponIssuedForAppendStrategyTest::class])->getGateway(EventStore::class);

        $eventStore->appendTo(self::STREAM, [new CouponIssuedForAppendStrategyTest('SUMMER24')]);

        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('coupon', 'SUMMER24'))->events);
    }

    public function test_with_the_boundary_enforces_an_aggregate_only_condition_through_the_tag_protocol(): void
    {
        $eventStore = $this->bootstrapEcotone(true)->getGateway(EventStore::class);
        $eventStore->appendTo(self::STREAM, [$this->aggregateEvent('order-1', 1)], AppendCondition::forAggregate('Order', 'order-1', 0));

        $this->expectException(ConcurrencyException::class);

        $eventStore->appendTo(self::STREAM, [$this->aggregateEvent('order-1', 2)], AppendCondition::forAggregate('Order', 'order-1', 0));
    }

    private function bootstrapEcotone(bool $dynamicConsistencyBoundaryEnabled = false, array $classesToResolve = []): FlowTestSupport
    {
        if (! $dynamicConsistencyBoundaryEnabled) {
            return EcotoneLite::bootstrapFlowTesting(classesToResolve: $classesToResolve, enabledModules: []);
        }

        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: $classesToResolve,
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

/**
 * licence Apache-2.0
 */
final readonly class CouponIssuedForAppendStrategyTest
{
    public function __construct(
        #[EventTag('coupon')] public string $code,
    ) {
    }
}
