<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
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
final class LiteralEventTagDecisionModelTest extends TestCase
{
    public function test_a_model_scoped_by_a_class_level_literal_tag_needs_no_value_on_the_command(): void
    {
        $ecotone = $this->bootstrap([
            InvoiceIssuingHandlerForLiteralTag::class,
            InvoiceNumberingForLiteralTag::class,
            InvoiceIssuedForLiteralTag::class,
        ], [new InvoiceIssuingHandlerForLiteralTag()]);

        $ecotone->sendCommand(new IssueInvoiceForLiteralTag('customer-1'));
        $ecotone->sendCommand(new IssueInvoiceForLiteralTag('customer-2'));
        $ecotone->sendCommand(new IssueInvoiceForLiteralTag('customer-1'));

        $this->assertSame([1, 2, 3], self::issuedNumbers($ecotone));
    }

    public function test_a_model_mixing_a_literal_tag_with_a_message_resolved_one_reads_only_the_message_resolved_one(): void
    {
        $ecotone = $this->bootstrap([
            RegionalInvoiceIssuingHandlerForLiteralTag::class,
            RegionalInvoiceNumberingForLiteralTag::class,
            RegionalInvoiceIssuedForLiteralTag::class,
        ], [new RegionalInvoiceIssuingHandlerForLiteralTag()]);

        $ecotone->sendCommand(new IssueRegionalInvoiceForLiteralTag('eu'));
        $ecotone->sendCommand(new IssueRegionalInvoiceForLiteralTag('us'));
        $ecotone->sendCommand(new IssueRegionalInvoiceForLiteralTag('eu'));

        $this->assertSame([1, 1, 2], self::issuedNumbers($ecotone));
    }

    public function test_a_numeric_literal_scopes_the_model_as_the_string_the_events_carry(): void
    {
        $ecotone = $this->bootstrap([
            BatchCountingHandlerForLiteralTag::class,
            BatchCountForLiteralTag::class,
            BatchItemAddedForLiteralTag::class,
        ], [new BatchCountingHandlerForLiteralTag()]);

        $ecotone->sendCommand(new AddBatchItemForLiteralTag());
        $ecotone->sendCommand(new AddBatchItemForLiteralTag());

        $this->assertSame([1, 2], self::issuedNumbers($ecotone));
    }

    public function test_handled_events_declaring_different_literals_for_one_scope_tag_are_refused_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("declare different class-level #[EventTag('invoiceSequence')] literals");

        $this->bootstrap([
            DisagreeingNumberingForLiteralTag::class,
            DefaultSequenceInvoiceIssuedForLiteralTag::class,
            ArchivedSequenceInvoiceIssuedForLiteralTag::class,
        ], []);
    }

    /**
     * @return int[]
     */
    private static function issuedNumbers(FlowTestSupport $ecotone): array
    {
        return array_map(
            static fn (object $event): int => $event->number,
            $ecotone->popRecordedEvents(),
        );
    }

    /**
     * @param class-string[] $classesToResolve
     * @param object[] $services
     */
    private function bootstrap(array $classesToResolve, array $services): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: $classesToResolve,
            containerOrAvailableServices: $services,
            configuration: ServiceConfiguration::createWithDefaults()->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

final readonly class IssueInvoiceForLiteralTag
{
    public function __construct(public string $customerId)
    {
    }
}

#[EventTag('invoiceSequence', value: 'default')]
final readonly class InvoiceIssuedForLiteralTag
{
    public function __construct(public int $number)
    {
    }
}

#[DecisionModel(tags: ['invoiceSequence'])]
final class InvoiceNumberingForLiteralTag
{
    private int $lastNumber = 0;

    #[Apply]
    public function issued(InvoiceIssuedForLiteralTag $event): void
    {
        $this->lastNumber = $event->number;
    }

    public function nextNumber(): int
    {
        return $this->lastNumber + 1;
    }
}

final class InvoiceIssuingHandlerForLiteralTag
{
    #[CommandHandler]
    public function issue(IssueInvoiceForLiteralTag $command, InvoiceNumberingForLiteralTag $numbering): array
    {
        return [new InvoiceIssuedForLiteralTag($numbering->nextNumber())];
    }
}

final readonly class IssueRegionalInvoiceForLiteralTag
{
    public function __construct(public string $region)
    {
    }
}

#[EventTag('invoiceSequence', value: 'regional')]
final readonly class RegionalInvoiceIssuedForLiteralTag
{
    public function __construct(
        #[EventTag('region')] public string $region,
        public int $number,
    ) {
    }
}

#[DecisionModel(tags: ['invoiceSequence', 'region'])]
final class RegionalInvoiceNumberingForLiteralTag
{
    private int $lastNumber = 0;

    #[Apply]
    public function issued(RegionalInvoiceIssuedForLiteralTag $event): void
    {
        $this->lastNumber = $event->number;
    }

    public function nextNumber(): int
    {
        return $this->lastNumber + 1;
    }
}

final class RegionalInvoiceIssuingHandlerForLiteralTag
{
    #[CommandHandler]
    public function issue(IssueRegionalInvoiceForLiteralTag $command, RegionalInvoiceNumberingForLiteralTag $numbering): array
    {
        return [new RegionalInvoiceIssuedForLiteralTag($command->region, $numbering->nextNumber())];
    }
}

final readonly class AddBatchItemForLiteralTag
{
}

#[EventTag('batch', value: '1')]
final readonly class BatchItemAddedForLiteralTag
{
    public function __construct(public int $number)
    {
    }
}

#[DecisionModel(tags: ['batch'])]
final class BatchCountForLiteralTag
{
    private int $lastNumber = 0;

    #[Apply]
    public function added(BatchItemAddedForLiteralTag $event): void
    {
        $this->lastNumber = $event->number;
    }

    public function nextNumber(): int
    {
        return $this->lastNumber + 1;
    }
}

final class BatchCountingHandlerForLiteralTag
{
    #[CommandHandler]
    public function add(AddBatchItemForLiteralTag $command, BatchCountForLiteralTag $batch): array
    {
        return [new BatchItemAddedForLiteralTag($batch->nextNumber())];
    }
}

#[EventTag('invoiceSequence', value: 'default')]
final readonly class DefaultSequenceInvoiceIssuedForLiteralTag
{
    public function __construct(public int $number)
    {
    }
}

#[EventTag('invoiceSequence', value: 'archived')]
final readonly class ArchivedSequenceInvoiceIssuedForLiteralTag
{
    public function __construct(public int $number)
    {
    }
}

#[DecisionModel(tags: ['invoiceSequence'])]
final class DisagreeingNumberingForLiteralTag
{
    #[Apply]
    public function issued(DefaultSequenceInvoiceIssuedForLiteralTag $event): void
    {
    }

    #[Apply]
    public function archived(ArchivedSequenceInvoiceIssuedForLiteralTag $event): void
    {
    }
}
