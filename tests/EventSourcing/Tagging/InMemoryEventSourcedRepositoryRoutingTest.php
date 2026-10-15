<?php

declare(strict_types=1);

namespace Test\Ecotone\EventSourcing\Tagging;

use Ecotone\Api\Attribute\AggregateType;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Modelling\WithAggregateVersioning;
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
final class InMemoryEventSourcedRepositoryRoutingTest extends TestCase
{
    public function test_aggregate_recorded_events_are_visible_to_a_tagged_event_store_load(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [OrderForRoutingTest::class, OrderPlacedForRoutingTest::class],
            configuration: ServiceConfiguration::createWithDefaults()->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->sendCommand(new PlaceOrderForRoutingTest('order-1', 'coupon-1'));

        /** @var EventStore $eventStore */
        $eventStore = $ecotone->getGateway(EventStore::class);

        $loadedEvents = $eventStore->loadByCriteria(EventCriteria::tag('coupon', 'coupon-1'));

        $this->assertCount(1, $loadedEvents->events);
        $this->assertInstanceOf(OrderPlacedForRoutingTest::class, $loadedEvents->events[0]->getPayload());
    }
}

final readonly class PlaceOrderForRoutingTest
{
    public function __construct(
        public string $orderId,
        public string $couponCode,
    ) {
    }
}

final readonly class OrderPlacedForRoutingTest
{
    public function __construct(
        public string $orderId,
        #[EventTag('coupon')] public string $couponCode,
    ) {
    }
}

#[EventSourcingAggregate]
#[AggregateType('OrderForRouting')]
final class OrderForRoutingTest
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $orderId;

    #[CommandHandler]
    public static function place(PlaceOrderForRoutingTest $command): array
    {
        return [new OrderPlacedForRoutingTest($command->orderId, $command->couponCode)];
    }

    #[Apply]
    public function whenPlaced(OrderPlacedForRoutingTest $event): void
    {
        $this->orderId = $event->orderId;
    }
}
