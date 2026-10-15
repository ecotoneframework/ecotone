<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\AggregateBoundary;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\AggregateType;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\EventSourcingSaga;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\Saga;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
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
final class AggregateBoundaryConfigurationTest extends TestCase
{
    public function test_state_stored_aggregate_without_aggregate_type_is_rejected_when_dcb_is_enabled(): void
    {
        try {
            $this->bootstrapWithDcb([WalletWithoutAggregateType::class]);
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(WalletWithoutAggregateType::class, $exception->getMessage());
            $this->assertStringContainsString('#[AggregateType', $exception->getMessage());
        }
    }

    public function test_event_sourced_aggregate_without_aggregate_type_is_rejected_when_dcb_is_enabled(): void
    {
        try {
            $this->bootstrapWithDcb([LedgerWithoutAggregateType::class, LedgerOpened::class]);
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(LedgerWithoutAggregateType::class, $exception->getMessage());
            $this->assertStringContainsString('#[AggregateType', $exception->getMessage());
        }
    }

    public function test_aggregates_declaring_aggregate_type_boot_when_dcb_is_enabled(): void
    {
        $ecotone = $this->bootstrapWithDcb([WalletWithAggregateType::class, LedgerWithAggregateType::class, LedgerOpened::class]);

        $this->assertInstanceOf(FlowTestSupport::class, $ecotone);
    }

    public function test_aggregates_without_aggregate_type_boot_when_dcb_is_disabled(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [WalletWithoutAggregateType::class, LedgerWithoutAggregateType::class, LedgerOpened::class],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([]),
        );

        $ecotone->sendCommandWithRouting('wallet.open', 'w-1');

        $this->assertSame('w-1', $ecotone->getAggregate(WalletWithoutAggregateType::class, 'w-1')->walletId);
    }

    public function test_saga_without_aggregate_type_boots_when_dcb_is_enabled(): void
    {
        $ecotone = $this->bootstrapWithDcb([OnboardingSagaWithoutAggregateType::class]);

        $this->assertInstanceOf(FlowTestSupport::class, $ecotone);
    }

    public function test_event_sourced_saga_without_aggregate_type_boots_when_dcb_is_enabled(): void
    {
        $ecotone = $this->bootstrapWithDcb([ShipmentSagaWithoutAggregateType::class, ShipmentStarted::class]);

        $this->assertInstanceOf(FlowTestSupport::class, $ecotone);
    }

    public function test_aggregate_type_whose_counter_tag_exceeds_the_tag_name_column_is_rejected_pointing_at_a_shorter_aggregate_type(): void
    {
        try {
            $this->bootstrapWithDcb([WalletWithTooLongAggregateType::class]);
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(WalletWithTooLongAggregateType::class, $exception->getMessage());
            $this->assertStringContainsString('100 characters', $exception->getMessage());
            $this->assertStringContainsString('shorter #[AggregateType', $exception->getMessage());
        }
    }

    public function test_aggregate_type_whose_counter_tag_is_exactly_the_tag_name_column_length_boots(): void
    {
        $ecotone = $this->bootstrapWithDcb([WalletWithLongestAllowedAggregateType::class]);

        $this->assertInstanceOf(FlowTestSupport::class, $ecotone);
    }

    public function test_event_tag_named_like_an_aggregate_counter_tag_is_rejected_naming_both(): void
    {
        try {
            $this->bootstrapWithDcb([WalletWithAggregateType::class, EventTaggedLikeTheWalletCounter::class]);
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString("'aggregate_Wallet'", $exception->getMessage());
            $this->assertStringContainsString(WalletWithAggregateType::class, $exception->getMessage());
            $this->assertStringContainsString(EventTaggedLikeTheWalletCounter::class, $exception->getMessage());
        }
    }

    public function test_event_tag_with_the_aggregate_prefix_that_names_no_aggregate_boots(): void
    {
        $ecotone = $this->bootstrapWithDcb([WalletWithAggregateType::class, EventTaggedWithUnrelatedAggregatePrefix::class]);

        $this->assertInstanceOf(FlowTestSupport::class, $ecotone);
    }

    /**
     * @param class-string[] $classesToResolve
     */
    private function bootstrapWithDcb(array $classesToResolve): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: $classesToResolve,
            configuration: ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

#[Aggregate]
final class WalletWithoutAggregateType
{
    public function __construct(#[Identifier] public string $walletId)
    {
    }

    #[CommandHandler('wallet.open')]
    public static function open(string $walletId): self
    {
        return new self($walletId);
    }
}

#[Aggregate]
#[AggregateType('Wallet')]
final class WalletWithAggregateType
{
    public function __construct(#[Identifier] public string $walletId)
    {
    }

    #[CommandHandler('wallet.open')]
    public static function open(string $walletId): self
    {
        return new self($walletId);
    }
}

#[Aggregate]
#[AggregateType('WalletTypeNameLongEnoughThatPrefixingItWithAggregateUnderscorePushesTheCounterTagPastTheColumn')]
final class WalletWithTooLongAggregateType
{
    public function __construct(#[Identifier] public string $walletId)
    {
    }

    #[CommandHandler('wallet.open')]
    public static function open(string $walletId): self
    {
        return new self($walletId);
    }
}

#[Aggregate]
#[AggregateType('ŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻółćŻó')]
final class WalletWithLongestAllowedAggregateType
{
    public function __construct(#[Identifier] public string $walletId)
    {
    }

    #[CommandHandler('wallet.open')]
    public static function open(string $walletId): self
    {
        return new self($walletId);
    }
}

#[EventSourcingAggregate]
final class LedgerWithoutAggregateType
{
    use WithAggregateVersioning;

    #[Identifier] private string $ledgerId;

    #[CommandHandler('ledger.open')]
    public static function open(string $ledgerId): array
    {
        return [new LedgerOpened($ledgerId)];
    }

    #[Apply]
    public function applyOpened(LedgerOpened $event): void
    {
        $this->ledgerId = $event->ledgerId;
    }
}

#[EventSourcingAggregate]
#[AggregateType('Ledger')]
final class LedgerWithAggregateType
{
    use WithAggregateVersioning;

    #[Identifier] private string $ledgerId;

    #[CommandHandler('ledger.open')]
    public static function open(string $ledgerId): array
    {
        return [new LedgerOpened($ledgerId)];
    }

    #[Apply]
    public function applyOpened(LedgerOpened $event): void
    {
        $this->ledgerId = $event->ledgerId;
    }
}

final readonly class LedgerOpened
{
    public function __construct(public string $ledgerId)
    {
    }
}

#[Saga]
final class OnboardingSagaWithoutAggregateType
{
    public function __construct(#[Identifier] public string $onboardingId)
    {
    }

    #[CommandHandler('onboarding.start')]
    public static function start(string $onboardingId): self
    {
        return new self($onboardingId);
    }
}

#[EventSourcingSaga]
final class ShipmentSagaWithoutAggregateType
{
    use WithAggregateVersioning;

    #[Identifier] private string $shipmentId;

    #[CommandHandler('shipment.start')]
    public static function start(string $shipmentId): array
    {
        return [new ShipmentStarted($shipmentId)];
    }

    #[Apply]
    public function applyStarted(ShipmentStarted $event): void
    {
        $this->shipmentId = $event->shipmentId;
    }
}

final readonly class ShipmentStarted
{
    public function __construct(public string $shipmentId)
    {
    }
}

final readonly class EventTaggedLikeTheWalletCounter
{
    public function __construct(#[EventTag('aggregate_Wallet')] public string $walletId)
    {
    }
}

final readonly class EventTaggedWithUnrelatedAggregatePrefix
{
    public function __construct(#[EventTag('aggregate_Invoice')] public string $invoiceId)
    {
    }
}
