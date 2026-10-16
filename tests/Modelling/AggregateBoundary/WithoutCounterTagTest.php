<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\AggregateBoundary;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\AggregateType;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionBoundary;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Fetch;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\EventSourcing\DecisionModelConcurrencyException;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
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
final class WithoutCounterTagTest extends TestCase
{
    public function test_an_excluded_state_stored_aggregate_keeps_last_write_wins(): void
    {
        $ecotone = $this->bootstrap(
            [UnlockedBasketForWithoutCounterTag::class, GuardedBasketForWithoutCounterTag::class],
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withoutCounterTagFor(UnlockedBasketForWithoutCounterTag::class),
        );
        $ecotone->sendCommandWithRouting('unlockedBasket.open', 'b-1');

        $ecotone->sendCommandWithRouting('unlockedBasket.addWhileAnotherAdditionLands', ['basketId' => 'b-1']);

        $this->assertSame($this->itemsWithTheBoundarySwitchedOff(), $ecotone->sendQueryWithRouting('unlockedBasket.items', metadata: ['aggregate.id' => 'b-1']));
    }

    public function test_a_state_stored_aggregate_outside_the_exclusion_still_conflicts(): void
    {
        $ecotone = $this->bootstrap(
            [UnlockedBasketForWithoutCounterTag::class, GuardedBasketForWithoutCounterTag::class],
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withoutCounterTagFor(UnlockedBasketForWithoutCounterTag::class),
        );
        $ecotone->sendCommandWithRouting('guardedBasket.open', 'b-1');

        $this->expectException(DecisionModelConcurrencyException::class);
        $this->expectExceptionMessage('GuardedBasket b-1 changed since it was loaded');

        $ecotone->sendCommandWithRouting('guardedBasket.addWhileAnotherAdditionLands', ['basketId' => 'b-1']);
    }

    public function test_fetching_an_excluded_aggregate_into_a_decision_handler_is_refused_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            UnlockedBasketForWithoutCounterTag::class . ' is fetched into ' . BasketCheckoutForWithoutCounterTag::class
            . '::checkout, a Dynamic Consistency Boundary handler, but it is listed in DynamicConsistencyBoundaryConfiguration::withoutCounterTagFor()'
        );

        $this->bootstrap(
            [UnlockedBasketForWithoutCounterTag::class, BasketCheckoutForWithoutCounterTag::class, BasketCheckedOutForWithoutCounterTag::class, BasketCheckoutCountForWithoutCounterTag::class],
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withoutCounterTagFor(UnlockedBasketForWithoutCounterTag::class),
        );
    }

    public function test_an_excluded_aggregate_must_still_declare_its_aggregate_type(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Aggregate ' . UntypedBasketForWithoutCounterTag::class . " must declare #[AggregateType('...')]"
        );

        $this->bootstrap(
            [UntypedBasketForWithoutCounterTag::class],
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withoutCounterTagFor(UntypedBasketForWithoutCounterTag::class),
        );
    }

    public function test_listing_an_event_sourced_aggregate_is_refused_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'DynamicConsistencyBoundaryConfiguration::withoutCounterTagFor() names ' . LedgerForWithoutCounterTag::class
            . ', which is an #[EventSourcingAggregate]'
        );

        $this->bootstrap(
            [LedgerForWithoutCounterTag::class, LedgerEntryAddedForWithoutCounterTag::class],
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withoutCounterTagFor(LedgerForWithoutCounterTag::class),
        );
    }

    public function test_listing_a_class_that_is_no_aggregate_is_refused_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'DynamicConsistencyBoundaryConfiguration::withoutCounterTagFor() names ' . BasketCheckedOutForWithoutCounterTag::class
            . ', which is not a state-stored #[Aggregate]'
        );

        $this->bootstrap(
            [UnlockedBasketForWithoutCounterTag::class, BasketCheckedOutForWithoutCounterTag::class],
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withoutCounterTagFor(BasketCheckedOutForWithoutCounterTag::class),
        );
    }

    public function test_a_boundary_scoped_only_by_an_excluded_aggregate_names_the_method_the_aggregate_and_the_option(): void
    {
        $ecotone = $this->bootstrap(
            [UnlockedBasketForWithoutCounterTag::class, BasketCheckedOutForWithoutCounterTag::class, ExcludedAggregateBoundaryForWithoutCounterTag::class],
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withoutCounterTagFor(UnlockedBasketForWithoutCounterTag::class),
            [new ExcludedAggregateBoundaryForWithoutCounterTag()],
        );

        try {
            $ecotone->sendCommand(new CheckOutUnlockedBasketForWithoutCounterTag('b-1'));
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(ExcludedAggregateBoundaryForWithoutCounterTag::class . '::boundary', $exception->getMessage());
            $this->assertStringContainsString(UnlockedBasketForWithoutCounterTag::class, $exception->getMessage());
            $this->assertStringContainsString('withoutCounterTagFor', $exception->getMessage());
        }
    }

    public function test_a_boundary_mixing_an_excluded_aggregate_with_a_counted_tag_scopes_the_append(): void
    {
        $ecotone = $this->bootstrap(
            [UnlockedBasketForWithoutCounterTag::class, BasketCheckedOutForWithoutCounterTag::class, MixedExcludedAggregateBoundaryForWithoutCounterTag::class],
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withoutCounterTagFor(UnlockedBasketForWithoutCounterTag::class),
            [new MixedExcludedAggregateBoundaryForWithoutCounterTag()],
        );

        $ecotone->sendCommand(new CheckOutUnlockedBasketForWithoutCounterTag('b-1'));

        /** @var EventStore $eventStore */
        $eventStore = $ecotone->getGateway(EventStore::class);
        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('basket', 'b-1'))->events);
    }

    public function test_a_boundary_scoped_only_by_an_aggregate_outside_the_exclusion_scopes_the_append(): void
    {
        $ecotone = $this->bootstrap(
            [UnlockedBasketForWithoutCounterTag::class, GuardedBasketForWithoutCounterTag::class, BasketCheckedOutForWithoutCounterTag::class, GuardedAggregateBoundaryForWithoutCounterTag::class],
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()->withoutCounterTagFor(UnlockedBasketForWithoutCounterTag::class),
            [new GuardedAggregateBoundaryForWithoutCounterTag()],
        );

        $ecotone->sendCommand(new CheckOutUnlockedBasketForWithoutCounterTag('b-1'));

        /** @var EventStore $eventStore */
        $eventStore = $ecotone->getGateway(EventStore::class);
        $this->assertCount(1, $eventStore->loadByCriteria(EventCriteria::tag('basket', 'b-1'))->events);
    }

    private function itemsWithTheBoundarySwitchedOff(): int
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(classesToResolve: [UnlockedBasketForWithoutCounterTag::class], enabledModules: []);
        $ecotone->sendCommandWithRouting('unlockedBasket.open', 'b-1');
        $ecotone->sendCommandWithRouting('unlockedBasket.addWhileAnotherAdditionLands', ['basketId' => 'b-1']);

        return $ecotone->sendQueryWithRouting('unlockedBasket.items', metadata: ['aggregate.id' => 'b-1']);
    }

    /**
     * @param class-string[] $classesToResolve
     */
    private function bootstrap(array $classesToResolve, DynamicConsistencyBoundaryConfiguration $dynamicConsistencyBoundary, array $services = []): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: $classesToResolve,
            containerOrAvailableServices: $services,
            extensionObjects: [$dynamicConsistencyBoundary],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

#[Aggregate]
#[AggregateType('UnlockedBasket')]
final class UnlockedBasketForWithoutCounterTag
{
    private int $items = 0;

    private function __construct(#[Identifier] private string $basketId)
    {
    }

    #[CommandHandler('unlockedBasket.open')]
    public static function open(string $basketId): self
    {
        return new self($basketId);
    }

    #[CommandHandler('unlockedBasket.add')]
    public function add(array $command): void
    {
        $this->items++;
    }

    #[CommandHandler('unlockedBasket.addWhileAnotherAdditionLands')]
    public function addWhileAnotherAdditionLands(array $command, CommandBus $commandBus): void
    {
        $commandBus->sendWithRouting('unlockedBasket.add', ['basketId' => $this->basketId]);

        $this->items++;
    }

    #[QueryHandler('unlockedBasket.items')]
    public function items(): int
    {
        return $this->items;
    }
}

#[Aggregate]
#[AggregateType('GuardedBasket')]
final class GuardedBasketForWithoutCounterTag
{
    private int $items = 0;

    private function __construct(#[Identifier] private string $basketId)
    {
    }

    #[CommandHandler('guardedBasket.open')]
    public static function open(string $basketId): self
    {
        return new self($basketId);
    }

    #[CommandHandler('guardedBasket.add')]
    public function add(array $command): void
    {
        $this->items++;
    }

    #[CommandHandler('guardedBasket.addWhileAnotherAdditionLands')]
    public function addWhileAnotherAdditionLands(array $command, CommandBus $commandBus): void
    {
        $commandBus->sendWithRouting('guardedBasket.add', ['basketId' => $this->basketId]);

        $this->items++;
    }

    #[QueryHandler('guardedBasket.items')]
    public function items(): int
    {
        return $this->items;
    }
}

#[Aggregate]
final class UntypedBasketForWithoutCounterTag
{
    private function __construct(#[Identifier] private string $basketId)
    {
    }

    #[CommandHandler('untypedBasket.open')]
    public static function open(string $basketId): self
    {
        return new self($basketId);
    }
}

final readonly class CheckOutBasketForWithoutCounterTag
{
    public function __construct(public string $basketId)
    {
    }
}

final readonly class BasketCheckedOutForWithoutCounterTag
{
    public function __construct(
        #[EventTag('basket')] public string $basketId,
    ) {
    }
}

#[DecisionModel]
final class BasketCheckoutCountForWithoutCounterTag
{
    public int $checkouts = 0;

    #[Apply]
    public function when(BasketCheckedOutForWithoutCounterTag $event): void
    {
        $this->checkouts++;
    }
}

final class BasketCheckoutForWithoutCounterTag
{
    #[CommandHandler]
    public function checkout(
        CheckOutBasketForWithoutCounterTag $command,
        BasketCheckoutCountForWithoutCounterTag $checkoutCount,
        #[Fetch('payload.basketId')] UnlockedBasketForWithoutCounterTag $basket,
    ): array {
        return [new BasketCheckedOutForWithoutCounterTag($command->basketId)];
    }
}

final readonly class LedgerEntryAddedForWithoutCounterTag
{
    public function __construct(public string $ledgerId)
    {
    }
}

#[EventSourcingAggregate]
#[AggregateType('Ledger')]
final class LedgerForWithoutCounterTag
{
    #[Identifier]
    private string $ledgerId;

    #[CommandHandler('ledger.open')]
    public static function open(string $ledgerId): array
    {
        return [new LedgerEntryAddedForWithoutCounterTag($ledgerId)];
    }

    #[Apply]
    public function applyEntryAdded(LedgerEntryAddedForWithoutCounterTag $event): void
    {
        $this->ledgerId = $event->ledgerId;
    }
}

final readonly class CheckOutUnlockedBasketForWithoutCounterTag
{
    public function __construct(public string $basketId)
    {
    }
}

final class ExcludedAggregateBoundaryForWithoutCounterTag
{
    #[CommandHandler]
    public function checkout(CheckOutUnlockedBasketForWithoutCounterTag $command): array
    {
        return [new BasketCheckedOutForWithoutCounterTag($command->basketId)];
    }

    #[DecisionBoundary]
    public static function boundary(CheckOutUnlockedBasketForWithoutCounterTag $command): EventCriteria
    {
        return EventCriteria::aggregate(UnlockedBasketForWithoutCounterTag::class, $command->basketId);
    }
}

final class MixedExcludedAggregateBoundaryForWithoutCounterTag
{
    #[CommandHandler]
    public function checkout(CheckOutUnlockedBasketForWithoutCounterTag $command): array
    {
        return [new BasketCheckedOutForWithoutCounterTag($command->basketId)];
    }

    #[DecisionBoundary]
    public static function boundary(CheckOutUnlockedBasketForWithoutCounterTag $command): EventCriteria
    {
        return EventCriteria::aggregate(UnlockedBasketForWithoutCounterTag::class, $command->basketId)->andTag('basket', $command->basketId);
    }
}

final class GuardedAggregateBoundaryForWithoutCounterTag
{
    #[CommandHandler]
    public function checkout(CheckOutUnlockedBasketForWithoutCounterTag $command): array
    {
        return [new BasketCheckedOutForWithoutCounterTag($command->basketId)];
    }

    #[DecisionBoundary]
    public static function boundary(CheckOutUnlockedBasketForWithoutCounterTag $command): EventCriteria
    {
        return EventCriteria::aggregate(GuardedBasketForWithoutCounterTag::class, $command->basketId);
    }
}
