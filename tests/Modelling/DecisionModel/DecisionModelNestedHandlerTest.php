<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\EventSourcing\DecisionModelConcurrencyException;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\EcotoneLite;
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
final class DecisionModelNestedHandlerTest extends TestCase
{
    public function test_a_handler_whose_nested_command_appends_to_the_same_tag_fails_with_a_message_naming_that_cause(): void
    {
        $outer = new EnrolmentHandlerForNestedTest();
        $inner = new WaitlistHandlerForNestedTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$outer::class, $inner::class, SeatCountForNestedTest::class, SeatTakenForNestedTest::class],
            containerOrAvailableServices: [$outer, $inner],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        try {
            $ecotone->sendCommand(new EnrolForNestedTest('course-1'));
            $this->fail('Expected a DecisionModelConcurrencyException');
        } catch (DecisionModelConcurrencyException $exception) {
            $this->assertStringContainsString('course:course-1', $exception->getMessage());
            $this->assertStringContainsString('earlier append in the same', $exception->getMessage());
        }
    }
}

final readonly class EnrolForNestedTest
{
    public function __construct(
        public string $courseId,
    ) {
    }
}

final readonly class JoinWaitlistForNestedTest
{
    public function __construct(
        public string $courseId,
    ) {
    }
}

final readonly class SeatTakenForNestedTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
    ) {
    }
}

#[DecisionModel]
final class SeatCountForNestedTest
{
    public int $taken = 0;

    #[Apply]
    public function when(SeatTakenForNestedTest $event): void
    {
        $this->taken++;
    }
}

final class EnrolmentHandlerForNestedTest
{
    #[CommandHandler]
    public function enrol(EnrolForNestedTest $command, SeatCountForNestedTest $seats, CommandBus $commandBus): array
    {
        $commandBus->send(new JoinWaitlistForNestedTest($command->courseId));

        return [new SeatTakenForNestedTest($command->courseId)];
    }
}

final class WaitlistHandlerForNestedTest
{
    #[CommandHandler]
    public function join(JoinWaitlistForNestedTest $command, SeatCountForNestedTest $seats): array
    {
        return [new SeatTakenForNestedTest($command->courseId)];
    }
}
