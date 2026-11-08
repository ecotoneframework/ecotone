<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\AggregateType;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionBoundary;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\EventSourcing\DecisionModelConcurrencyException;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;

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
final class DecisionModelConflictNamingTest extends TestCase
{
    public function test_a_tag_conflict_names_the_decision_model_scoped_by_the_conflicting_tag(): void
    {
        $ecotone = $this->bootstrap([
            SeatCountForConflictNaming::class,
            SeatTakenForConflictNaming::class,
            EnrolmentHandlerForConflictNaming::class,
            WaitlistHandlerForConflictNaming::class,
        ], [new EnrolmentHandlerForConflictNaming(), new WaitlistHandlerForConflictNaming()]);

        try {
            $ecotone->sendCommand(new EnrolForConflictNaming('course-1'));
            $this->fail('Expected a DecisionModelConcurrencyException');
        } catch (DecisionModelConcurrencyException $exception) {
            $this->assertStringContainsString('on tag course:course-1 (expected version 0, moved since it was read) while deciding ' . SeatCountForConflictNaming::class, $exception->getMessage());
            $this->assertStringContainsString('retry the command: InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true, 3, [DecisionModelConcurrencyException::class])', $exception->getMessage());
            $this->assertStringContainsString('The command bus retries by default when DbalConfiguration runs it in a database transaction', $exception->getMessage());
        }
    }

    public function test_a_tag_conflict_names_every_decision_model_whose_scope_contains_the_tag(): void
    {
        $ecotone = $this->bootstrap([
            SeatCountForConflictNaming::class,
            WaitlistLengthForConflictNaming::class,
            SeatTakenForConflictNaming::class,
            TwoModelEnrolmentHandlerForConflictNaming::class,
            WaitlistHandlerForConflictNaming::class,
        ], [new TwoModelEnrolmentHandlerForConflictNaming(), new WaitlistHandlerForConflictNaming()]);

        try {
            $ecotone->sendCommand(new EnrolWithTwoModelsForConflictNaming('course-1'));
            $this->fail('Expected a DecisionModelConcurrencyException');
        } catch (DecisionModelConcurrencyException $exception) {
            $this->assertStringContainsString(SeatCountForConflictNaming::class, $exception->getMessage());
            $this->assertStringContainsString(WaitlistLengthForConflictNaming::class, $exception->getMessage());
        }
    }

    public function test_a_boundary_only_handler_conflict_names_the_boundary_method(): void
    {
        $ecotone = $this->bootstrap([
            SeatTakenForConflictNaming::class,
            BoundaryOnlyHandlerForConflictNaming::class,
            WaitlistHandlerForConflictNaming::class,
            SeatCountForConflictNaming::class,
        ], [new BoundaryOnlyHandlerForConflictNaming(), new WaitlistHandlerForConflictNaming()]);

        try {
            $ecotone->sendCommand(new EnrolWithBoundaryForConflictNaming('course-1'));
            $this->fail('Expected a DecisionModelConcurrencyException');
        } catch (DecisionModelConcurrencyException $exception) {
            $this->assertStringContainsString(
                'while deciding ' . BoundaryOnlyHandlerForConflictNaming::class . '::boundary',
                $exception->getMessage(),
            );
        }
    }

    public function test_an_aggregate_counter_conflict_keeps_the_aggregate_rendered_text_and_names_no_model(): void
    {
        $ecotone = $this->bootstrap([
            PurseForConflictNaming::class,
            PurseSpendCountForConflictNaming::class,
            PurseSpentForConflictNaming::class,
        ], []);

        $ecotone->sendCommand(new OpenPurseForConflictNaming('p-1'));

        try {
            $ecotone->sendCommand(new SpendWhileAnotherSpendLandsForConflictNaming('p-1'));
            $this->fail('Expected a DecisionModelConcurrencyException');
        } catch (DecisionModelConcurrencyException $exception) {
            $this->assertStringContainsString('ConflictPurse p-1 changed since it was loaded at change 1', $exception->getMessage());
            $this->assertStringContainsString('retry the command: InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true, 3, [DecisionModelConcurrencyException::class])', $exception->getMessage());
            $this->assertStringNotContainsString('while deciding', $exception->getMessage());
        }
    }

    public function test_a_conflict_is_reported_once_as_a_structured_notice_so_it_can_be_counted_without_tracing(): void
    {
        $conflictRecorder = new ConflictRecordingLoggerForConflictNaming();

        $ecotone = $this->bootstrap([
            SeatCountForConflictNaming::class,
            SeatTakenForConflictNaming::class,
            EnrolmentHandlerForConflictNaming::class,
            WaitlistHandlerForConflictNaming::class,
        ], [new EnrolmentHandlerForConflictNaming(), new WaitlistHandlerForConflictNaming(), 'logger' => $conflictRecorder]);

        try {
            $ecotone->sendCommand(new EnrolForConflictNaming('course-1'));
        } catch (DecisionModelConcurrencyException) {
        }

        $this->assertSame([[
            DecisionModelConcurrencyException::CONFLICT_TAG_FIELD => 'course:course-1',
            DecisionModelConcurrencyException::CONFLICT_EXPECTED_VERSION_FIELD => 0,
            DecisionModelConcurrencyException::CONFLICT_MODEL_FIELD => SeatCountForConflictNaming::class,
        ]], $conflictRecorder->conflicts);
    }

    /**
     * @param class-string[] $classesToResolve
     * @param array<int|string, object> $services
     */
    private function bootstrap(array $classesToResolve, array $services): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: $classesToResolve,
            containerOrAvailableServices: $services,
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

/**
 * @internal
 */
final class ConflictRecordingLoggerForConflictNaming extends AbstractLogger
{
    /**
     * @var array<array<string, mixed>>
     */
    public array $conflicts = [];

    public function log($level, Stringable|string $message, array $context = []): void
    {
        if ($level === LogLevel::NOTICE && (string) $message === DecisionModelConcurrencyException::LOG_MESSAGE) {
            $this->conflicts[] = $context;
        }
    }
}

final readonly class EnrolForConflictNaming
{
    public function __construct(public string $courseId)
    {
    }
}

final readonly class EnrolWithTwoModelsForConflictNaming
{
    public function __construct(public string $courseId)
    {
    }
}

final readonly class EnrolWithBoundaryForConflictNaming
{
    public function __construct(public string $courseId)
    {
    }
}

final readonly class JoinWaitlistForConflictNaming
{
    public function __construct(public string $courseId)
    {
    }
}

final readonly class SeatTakenForConflictNaming
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
    ) {
    }
}

#[DecisionModel]
final class SeatCountForConflictNaming
{
    public int $taken = 0;

    #[Apply]
    public function when(SeatTakenForConflictNaming $event): void
    {
        $this->taken++;
    }
}

#[DecisionModel]
final class WaitlistLengthForConflictNaming
{
    public int $waiting = 0;

    #[Apply]
    public function when(SeatTakenForConflictNaming $event): void
    {
        $this->waiting++;
    }
}

final class WaitlistHandlerForConflictNaming
{
    #[CommandHandler]
    public function join(JoinWaitlistForConflictNaming $command, SeatCountForConflictNaming $seats): array
    {
        return [new SeatTakenForConflictNaming($command->courseId)];
    }
}

final class EnrolmentHandlerForConflictNaming
{
    #[CommandHandler]
    public function enrol(EnrolForConflictNaming $command, SeatCountForConflictNaming $seats, CommandBus $commandBus): array
    {
        $commandBus->send(new JoinWaitlistForConflictNaming($command->courseId));

        return [new SeatTakenForConflictNaming($command->courseId)];
    }
}

final class TwoModelEnrolmentHandlerForConflictNaming
{
    #[CommandHandler]
    public function enrol(
        EnrolWithTwoModelsForConflictNaming $command,
        SeatCountForConflictNaming $seats,
        WaitlistLengthForConflictNaming $waitlist,
        CommandBus $commandBus,
    ): array {
        $commandBus->send(new JoinWaitlistForConflictNaming($command->courseId));

        return [new SeatTakenForConflictNaming($command->courseId)];
    }
}

final class BoundaryOnlyHandlerForConflictNaming
{
    #[DecisionBoundary]
    public static function boundary(EnrolWithBoundaryForConflictNaming $command): EventCriteria
    {
        return EventCriteria::tag('course', $command->courseId);
    }

    #[CommandHandler]
    public function enrol(EnrolWithBoundaryForConflictNaming $command, CommandBus $commandBus): array
    {
        $commandBus->send(new JoinWaitlistForConflictNaming($command->courseId));

        return [new SeatTakenForConflictNaming($command->courseId)];
    }
}

final readonly class OpenPurseForConflictNaming
{
    public function __construct(public string $purseId)
    {
    }
}

final readonly class SpendWhileAnotherSpendLandsForConflictNaming
{
    public function __construct(public string $purseId)
    {
    }
}

final readonly class SpendFromPurseForConflictNaming
{
    public function __construct(public string $purseId)
    {
    }
}

final readonly class PurseSpentForConflictNaming
{
    public function __construct(
        #[EventTag('purse')] public string $purseId,
    ) {
    }
}

#[DecisionModel]
final class PurseSpendCountForConflictNaming
{
    public int $spent = 0;

    #[Apply]
    public function when(PurseSpentForConflictNaming $event): void
    {
        $this->spent++;
    }
}

#[Aggregate]
#[AggregateType('ConflictPurse')]
final class PurseForConflictNaming
{
    #[Identifier]
    private string $purseId;

    private int $spent = 0;

    #[CommandHandler]
    public static function open(OpenPurseForConflictNaming $command): self
    {
        $purse = new self();
        $purse->purseId = $command->purseId;

        return $purse;
    }

    #[CommandHandler]
    public function spend(SpendFromPurseForConflictNaming $command): void
    {
        $this->spent++;
    }

    #[CommandHandler]
    public function spendWhileAnotherSpendLands(
        SpendWhileAnotherSpendLandsForConflictNaming $command,
        PurseSpendCountForConflictNaming $spendCount,
        CommandBus $commandBus,
    ): void {
        $commandBus->send(new SpendFromPurseForConflictNaming($command->purseId));

        $this->spent++;
    }
}
