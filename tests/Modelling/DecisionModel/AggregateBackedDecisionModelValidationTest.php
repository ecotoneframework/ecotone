<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\AggregateType;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\EventSourcingSaga;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\Stream;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
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
final class AggregateBackedDecisionModelValidationTest extends TestCase
{
    public function test_aggregate_declared_together_with_tags_is_rejected_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('never both');

        $this->bootstrapWith([ModelScopedByBothAggregateAndTagsForAggregateBackedValidationTest::class]);
    }

    public function test_aggregate_naming_a_state_stored_aggregate_is_rejected_naming_fetch_as_the_alternative(): void
    {
        try {
            $this->bootstrapWith([ModelBackedByStateStoredAggregateForAggregateBackedValidationTest::class, StateStoredLedgerForAggregateBackedValidationTest::class]);
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(StateStoredLedgerForAggregateBackedValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString('records no events', $exception->getMessage());
            $this->assertStringContainsString('#[Fetch]', $exception->getMessage());
        }
    }

    public function test_aggregate_naming_a_saga_is_rejected_at_bootstrap(): void
    {
        try {
            $this->bootstrapWith([ModelBackedBySagaForAggregateBackedValidationTest::class, SagaForAggregateBackedValidationTest::class]);
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(SagaForAggregateBackedValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString('saga', $exception->getMessage());
        }
    }

    public function test_aggregate_naming_a_class_that_is_no_aggregate_is_rejected_at_bootstrap(): void
    {
        try {
            $this->bootstrapWith([ModelBackedByPlainClassForAggregateBackedValidationTest::class]);
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(PlainClassForAggregateBackedValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString('#[EventSourcingAggregate]', $exception->getMessage());
        }
    }

    public function test_handled_event_the_backing_aggregate_does_not_record_is_rejected_naming_model_event_and_aggregate(): void
    {
        try {
            $this->bootstrapWith([ModelHandlingAnEventTheWalletDoesNotRecordForAggregateBackedValidationTest::class, WalletForAggregateBackedValidationTest::class]);
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(ModelHandlingAnEventTheWalletDoesNotRecordForAggregateBackedValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString(PayoutRequestedForAggregateBackedValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString(WalletForAggregateBackedValidationTest::class, $exception->getMessage());
        }
    }

    public function test_identifier_unresolvable_from_a_concrete_message_is_rejected_naming_the_identifier_and_fetch(): void
    {
        $handler = new HandlerWithoutWalletIdentifierForAggregateBackedValidationTest();

        try {
            $this->bootstrapWith(
                [$handler::class, WalletBalanceForAggregateBackedValidationTest::class, WalletForAggregateBackedValidationTest::class],
                [$handler],
            );
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(WalletBalanceForAggregateBackedValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString("'walletId'", $exception->getMessage());
            $this->assertStringContainsString('#[Fetch]', $exception->getMessage());
        }
    }

    public function test_backing_aggregate_stream_on_another_connection_is_rejected_naming_both_connections(): void
    {
        if (! class_exists(Stream::class)) {
            $this->markTestSkipped('Ecotone\Api\EventSourcing\Stream is not autoloadable outside a monorepo context that includes ecotone/pdo-event-sourcing.');
        }

        $handler = new HandlerOnDefaultConnectionForAggregateBackedValidationTest();

        try {
            $this->bootstrapWith(
                [$handler::class, ArchivedWalletBalanceForAggregateBackedValidationTest::class, ArchivedWalletForAggregateBackedValidationTest::class],
                [$handler],
            );
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(ArchivedWalletBalanceForAggregateBackedValidationTest::class, $exception->getMessage());
            $this->assertStringContainsString('reporting', $exception->getMessage());
        }
    }

    public function test_a_model_over_untagged_events_names_the_aggregate_remedy(): void
    {
        try {
            $this->bootstrapWith([ModelOverUntaggedWalletEventsForAggregateBackedValidationTest::class, WalletForAggregateBackedValidationTest::class]);
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString('#[DecisionModel(aggregate:', $exception->getMessage());
        }
    }

    /**
     * @param class-string[] $classesToResolve
     * @param object[] $services
     */
    private function bootstrapWith(array $classesToResolve, array $services = []): void
    {
        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: $classesToResolve,
            containerOrAvailableServices: $services,
            configuration: ServiceConfiguration::createWithDefaults()->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

final readonly class WalletCreditedForAggregateBackedValidationTest
{
    public function __construct(
        public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class WalletDebitedForAggregateBackedValidationTest
{
    public function __construct(
        public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class PayoutRequestedForAggregateBackedValidationTest
{
    public function __construct(
        public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class RequestPayoutWithoutWalletIdentifierForAggregateBackedValidationTest
{
    public function __construct(
        public string $reference,
        public int $amount,
    ) {
    }
}

final readonly class RequestPayoutForAggregateBackedValidationTest
{
    public function __construct(
        public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class CreditWalletForAggregateBackedValidationTest
{
    public function __construct(
        public string $walletId,
        public int $amount,
    ) {
    }
}

#[EventSourcingAggregate]
#[AggregateType('WalletForAggregateBackedValidation')]
final class WalletForAggregateBackedValidationTest
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $walletId;

    #[CommandHandler]
    public static function credit(CreditWalletForAggregateBackedValidationTest $command): array
    {
        return [new WalletCreditedForAggregateBackedValidationTest($command->walletId, $command->amount)];
    }

    #[Apply]
    public function credited(WalletCreditedForAggregateBackedValidationTest $event): void
    {
        $this->walletId = $event->walletId;
    }

    #[Apply]
    public function debited(WalletDebitedForAggregateBackedValidationTest $event): void
    {
    }
}

#[Aggregate]
final class StateStoredLedgerForAggregateBackedValidationTest
{
    #[Identifier]
    private string $walletId;
}

#[EventSourcingSaga]
#[AggregateType('SagaForAggregateBackedValidation')]
final class SagaForAggregateBackedValidationTest
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $walletId;

    #[Apply]
    public function credited(WalletCreditedForAggregateBackedValidationTest $event): void
    {
        $this->walletId = $event->walletId;
    }
}

final class PlainClassForAggregateBackedValidationTest
{
}

#[DecisionModel(tags: ['wallet'], aggregate: WalletForAggregateBackedValidationTest::class)]
final class ModelScopedByBothAggregateAndTagsForAggregateBackedValidationTest
{
    #[Apply]
    public function credited(WalletCreditedForAggregateBackedValidationTest $event): void
    {
    }
}

#[DecisionModel(aggregate: StateStoredLedgerForAggregateBackedValidationTest::class)]
final class ModelBackedByStateStoredAggregateForAggregateBackedValidationTest
{
    #[Apply]
    public function credited(WalletCreditedForAggregateBackedValidationTest $event): void
    {
    }
}

#[DecisionModel(aggregate: SagaForAggregateBackedValidationTest::class)]
final class ModelBackedBySagaForAggregateBackedValidationTest
{
    #[Apply]
    public function credited(WalletCreditedForAggregateBackedValidationTest $event): void
    {
    }
}

#[DecisionModel(aggregate: PlainClassForAggregateBackedValidationTest::class)]
final class ModelBackedByPlainClassForAggregateBackedValidationTest
{
    #[Apply]
    public function credited(WalletCreditedForAggregateBackedValidationTest $event): void
    {
    }
}

#[DecisionModel(aggregate: WalletForAggregateBackedValidationTest::class)]
final class ModelHandlingAnEventTheWalletDoesNotRecordForAggregateBackedValidationTest
{
    #[Apply]
    public function credited(WalletCreditedForAggregateBackedValidationTest $event): void
    {
    }

    #[Apply]
    public function requested(PayoutRequestedForAggregateBackedValidationTest $event): void
    {
    }
}

#[DecisionModel]
final class ModelOverUntaggedWalletEventsForAggregateBackedValidationTest
{
    #[Apply]
    public function credited(WalletCreditedForAggregateBackedValidationTest $event): void
    {
    }
}

#[DecisionModel(aggregate: WalletForAggregateBackedValidationTest::class)]
final class WalletBalanceForAggregateBackedValidationTest
{
    private int $balance = 0;

    #[Apply]
    public function credited(WalletCreditedForAggregateBackedValidationTest $event): void
    {
        $this->balance += $event->amount;
    }

    public function balance(): int
    {
        return $this->balance;
    }
}

final class HandlerWithoutWalletIdentifierForAggregateBackedValidationTest
{
    #[CommandHandler]
    public function payOut(RequestPayoutWithoutWalletIdentifierForAggregateBackedValidationTest $command, WalletBalanceForAggregateBackedValidationTest $wallet): array
    {
        return [];
    }
}

#[EventSourcingAggregate]
#[AggregateType('ArchivedWalletForAggregateBackedValidation')]
#[Stream('archived_wallet_stream', connectionReferenceName: 'reporting')]
final class ArchivedWalletForAggregateBackedValidationTest
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $walletId;

    #[Apply]
    public function credited(WalletCreditedForAggregateBackedValidationTest $event): void
    {
        $this->walletId = $event->walletId;
    }
}

#[DecisionModel(aggregate: ArchivedWalletForAggregateBackedValidationTest::class)]
final class ArchivedWalletBalanceForAggregateBackedValidationTest
{
    private int $balance = 0;

    #[Apply]
    public function credited(WalletCreditedForAggregateBackedValidationTest $event): void
    {
        $this->balance += $event->amount;
    }

    public function balance(): int
    {
        return $this->balance;
    }
}

final class HandlerOnDefaultConnectionForAggregateBackedValidationTest
{
    #[CommandHandler]
    public function payOut(RequestPayoutForAggregateBackedValidationTest $command, ArchivedWalletBalanceForAggregateBackedValidationTest $wallet): array
    {
        return [];
    }
}
