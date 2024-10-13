<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\EventRevision\Person;
use Test\Ecotone\Modelling\Fixture\EventRevision\RegisterPerson;

/**
 * licence Enterprise
 * @internal
 */
final class EventSourcingAggregateTest extends TestCase
{
    public function test_registering_and_using_headers_in_event_sourcing_handler(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting([
            Person::class,
        ], licenceKey: LicenceTesting::VALID_LICENCE);


        $ecotoneLite->sendCommand(new RegisterPerson('123', 'premium'));

        $person = $ecotoneLite->getAggregate(Person::class, '123');

        self::assertEquals('123', $person->getPersonId());
        self::assertEquals('premium', $person->getType());
        self::assertEquals(2, $person->getRegisteredWithRevision());
    }

    public function test_failing_on_using_event_sourcing_handler_without_enterprise_mode(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting([
            Person::class,
        ]);


        $this->expectException(InvalidArgumentException::class);

        $ecotoneLite->sendCommand(new RegisterPerson('123', 'premium'));
    }
}
