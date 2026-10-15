<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\MediaTypeConverter;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Gateway\DocumentStore;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Store\Document\InMemoryDocumentStore;
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
final class DecisionModelSnapshotConfigurationTest extends TestCase
{
    public function test_snapshotting_a_class_that_is_not_a_decision_model_is_rejected_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [WalletBalanceForSnapshotConfigurationTest::class, WalletCreditedForSnapshotConfigurationTest::class],
            configuration: ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                DynamicConsistencyBoundaryConfiguration::createWithDefaults()
                    ->withSnapshotsFor(NotADecisionModelForSnapshotConfigurationTest::class),
            ])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_snapshotted_model_decides_the_same_as_an_unsnapshotted_one(): void
    {
        $withoutSnapshots = $this->amountsCreditedUnder(DynamicConsistencyBoundaryConfiguration::createWithDefaults());
        $withSnapshots = $this->amountsCreditedUnder(
            DynamicConsistencyBoundaryConfiguration::createWithDefaults()
                ->withSnapshotsFor(WalletBalanceForSnapshotConfigurationTest::class, thresholdTrigger: 2)
        );

        $this->assertSame([1, 2, 3, 4], $withoutSnapshots);
        $this->assertSame($withoutSnapshots, $withSnapshots);
    }

    /**
     * @return int[]
     */
    private function amountsCreditedUnder(DynamicConsistencyBoundaryConfiguration $boundaryConfiguration): array
    {
        $wallets = new WalletsForSnapshotConfigurationTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [
                WalletsForSnapshotConfigurationTest::class,
                WalletBalanceForSnapshotConfigurationTest::class,
                WalletCreditedForSnapshotConfigurationTest::class,
                WalletBalanceConverterForSnapshotConfigurationTest::class,
            ],
            containerOrAvailableServices: [
                $wallets,
                new WalletBalanceConverterForSnapshotConfigurationTest(),
                DocumentStore::class => InMemoryDocumentStore::createEmpty(),
            ],
            configuration: ServiceConfiguration::createWithDefaults()->withExtensionObjects([$boundaryConfiguration])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        foreach (range(1, 6) as $amount) {
            $ecotone->sendCommandWithRouting('wallet.credit', new CreditWalletForSnapshotConfigurationTest('wallet-1', $amount));
        }

        return array_map(
            static fn (WalletCreditedForSnapshotConfigurationTest $event): int => $event->amount,
            $ecotone->popRecordedEventsOfType(WalletCreditedForSnapshotConfigurationTest::class),
        );
    }
}

final readonly class CreditWalletForSnapshotConfigurationTest
{
    public function __construct(
        public string $walletId,
        public int $amount,
    ) {
    }
}

final readonly class WalletCreditedForSnapshotConfigurationTest
{
    public function __construct(
        #[EventTag('wallet')] public string $walletId,
        public int $amount,
    ) {
    }
}

#[DecisionModel]
final class WalletBalanceForSnapshotConfigurationTest
{
    private int $balance = 0;

    #[Apply]
    public function credited(WalletCreditedForSnapshotConfigurationTest $event): void
    {
        $this->balance += $event->amount;
    }

    public function staysWithinLimitAfter(int $amount): bool
    {
        return $this->balance + $amount <= 12;
    }

    public function balance(): int
    {
        return $this->balance;
    }

    public static function fromBalance(int $balance): self
    {
        $walletBalance = new self();
        $walletBalance->balance = $balance;

        return $walletBalance;
    }
}

final class NotADecisionModelForSnapshotConfigurationTest
{
}

#[MediaTypeConverter]
final class WalletBalanceConverterForSnapshotConfigurationTest implements Converter
{
    public function convert($source, Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType)
    {
        return $targetMediaType->isCompatibleWith(MediaType::createApplicationJson())
            ? json_encode(['balance' => $source->balance()])
            : WalletBalanceForSnapshotConfigurationTest::fromBalance(json_decode($source, true)['balance']);
    }

    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType->getTypeHint() === WalletBalanceForSnapshotConfigurationTest::class
            || $targetType->getTypeHint() === WalletBalanceForSnapshotConfigurationTest::class;
    }
}

final class WalletsForSnapshotConfigurationTest
{
    #[CommandHandler('wallet.credit')]
    public function credit(CreditWalletForSnapshotConfigurationTest $command, WalletBalanceForSnapshotConfigurationTest $balance): array
    {
        return $balance->staysWithinLimitAfter($command->amount)
            ? [new WalletCreditedForSnapshotConfigurationTest($command->walletId, $command->amount)]
            : [];
    }
}
