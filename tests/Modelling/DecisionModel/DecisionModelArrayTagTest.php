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
final class DecisionModelArrayTagTest extends TestCase
{
    public function test_a_model_scoped_by_one_value_of_an_array_valued_event_tag_sees_that_event(): void
    {
        $handler = new ReserveSeatHandlerForArrayTagTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, SeatReservationForArrayTagTest::class, SeatsReservedForArrayTagTest::class],
            containerOrAvailableServices: [$handler],
            configuration: ServiceConfiguration::createWithDefaults()->withExtensionObjects([DynamicConsistencyBoundaryConfiguration::createWithDefaults()])
                ->withEnabledModulePackageNames([]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->withEvents([new SeatsReservedForArrayTagTest(['seat-1', 'seat-2'])]);

        $ecotone->sendCommand(new ReserveSeatForArrayTagTest('seat-1'));

        $this->assertTrue(ReserveSeatHandlerForArrayTagTest::$observedReserved);

        $ecotone->sendCommand(new ReserveSeatForArrayTagTest('seat-3'));

        $this->assertFalse(ReserveSeatHandlerForArrayTagTest::$observedReserved);
    }
}

final readonly class ReserveSeatForArrayTagTest
{
    public function __construct(
        public string $seatId,
    ) {
    }
}

final readonly class SeatsReservedForArrayTagTest
{
    /**
     * @param string[] $seatIds
     */
    public function __construct(
        #[EventTag('seat')] public array $seatIds,
    ) {
    }
}

#[DecisionModel]
final class SeatReservationForArrayTagTest
{
    private bool $reserved = false;

    #[Apply]
    public function reserved(SeatsReservedForArrayTagTest $event): void
    {
        $this->reserved = true;
    }

    public function isReserved(): bool
    {
        return $this->reserved;
    }
}

final class ReserveSeatHandlerForArrayTagTest
{
    public static bool $observedReserved = false;

    #[CommandHandler]
    public function reserve(ReserveSeatForArrayTagTest $command, SeatReservationForArrayTagTest $seat): array
    {
        self::$observedReserved = $seat->isReserved();

        return [];
    }
}
