<?php

declare(strict_types=1);

namespace Test\Ecotone\EventSourcing\Tagging;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionBoundary;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\EventSourcing\AppendCondition;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Support\LicensingException;
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
final class DynamicConsistencyBoundaryDisabledTest extends TestCase
{
    private const DISABLED_MESSAGE = 'Dynamic Consistency Boundary is disabled. Register DynamicConsistencyBoundaryConfiguration::createWithDefaults() as an extension object (#[ServiceContext]) to enable decision models, event tags and append conditions.';

    public function test_handler_injecting_a_decision_model_fails_at_bootstrap_when_the_boundary_is_not_enabled(): void
    {
        $handler = new class () {
            #[CommandHandler]
            public function rename(RenameForDisabledBoundaryTest $command, RenamesForDisabledBoundaryTest $renames): array
            {
                return [new RenamedForDisabledBoundaryTest($command->id)];
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(self::DISABLED_MESSAGE);

        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, RenamesForDisabledBoundaryTest::class, RenamedForDisabledBoundaryTest::class],
            containerOrAvailableServices: [$handler],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_decision_model_class_alone_fails_at_bootstrap_when_the_boundary_is_not_enabled(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(self::DISABLED_MESSAGE);

        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [RenamesForDisabledBoundaryTest::class, RenamedForDisabledBoundaryTest::class],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_decision_boundary_fails_at_bootstrap_when_the_boundary_is_not_enabled(): void
    {
        $handler = new class () {
            #[DecisionBoundary]
            public static function boundary(RenameForDisabledBoundaryTest $command): EventCriteria
            {
                return EventCriteria::tag('entity', $command->id);
            }

            #[CommandHandler]
            public function rename(RenameForDisabledBoundaryTest $command): array
            {
                return [new RenamedForDisabledBoundaryTest($command->id)];
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(self::DISABLED_MESSAGE);

        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, RenamedForDisabledBoundaryTest::class],
            containerOrAvailableServices: [$handler],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_aggregate_emitting_tagged_events_works_when_the_boundary_is_not_enabled(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [EntityForDisabledBoundaryTest::class],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->sendCommand(new CreateEntityForDisabledBoundaryTest('entity-1'));
        $ecotone->sendCommand(new RenameEntityForDisabledBoundaryTest('entity-1'));

        $this->assertSame(2, $ecotone->getAggregate(EntityForDisabledBoundaryTest::class, 'entity-1')->version());
    }

    public function test_loading_by_criteria_throws_when_the_boundary_is_not_enabled(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [EntityForDisabledBoundaryTest::class],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(self::DISABLED_MESSAGE);

        $ecotone->getGateway(EventStore::class)->loadByCriteria(EventCriteria::tag('entity', 'entity-1'));
    }

    public function test_appending_with_a_tag_append_condition_throws_when_the_boundary_is_not_enabled(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [EntityForDisabledBoundaryTest::class],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(self::DISABLED_MESSAGE);

        $ecotone->getGateway(EventStore::class)->appendTo(
            'ecotone_event_stream',
            [new RenamedForDisabledBoundaryTest('entity-1')],
            AppendCondition::fromCapturedVersions([['name' => 'entity', 'value' => 'entity-1', 'expectedVersion' => 0]]),
        );
    }

    public function test_enabling_the_boundary_without_an_enterprise_licence_fails_at_bootstrap(): void
    {
        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [UntaggedForDisabledBoundaryTest::class],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
        );
    }
}

final readonly class CreateEntityForDisabledBoundaryTest
{
    public function __construct(public string $id)
    {
    }
}

final readonly class RenameEntityForDisabledBoundaryTest
{
    public function __construct(public string $id)
    {
    }
}

final readonly class RenameForDisabledBoundaryTest
{
    public function __construct(public string $id)
    {
    }
}

final readonly class UntaggedForDisabledBoundaryTest
{
    public function __construct(public string $id)
    {
    }
}

final readonly class RenamedForDisabledBoundaryTest
{
    public function __construct(#[EventTag('entity')] public string $id)
    {
    }
}

#[DecisionModel]
final class RenamesForDisabledBoundaryTest
{
    #[Apply]
    public function renamed(RenamedForDisabledBoundaryTest $event): void
    {
    }
}

#[EventSourcingAggregate]
final class EntityForDisabledBoundaryTest
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $id;

    #[CommandHandler]
    public static function create(CreateEntityForDisabledBoundaryTest $command): array
    {
        return [new RenamedForDisabledBoundaryTest($command->id)];
    }

    #[CommandHandler]
    public function rename(RenameEntityForDisabledBoundaryTest $command): array
    {
        return [new RenamedForDisabledBoundaryTest($this->id)];
    }

    #[Apply]
    public function whenRenamed(RenamedForDisabledBoundaryTest $event): void
    {
        $this->id = $event->id;
    }

    public function version(): int
    {
        return $this->version;
    }
}
