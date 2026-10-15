<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder\CreateAggregate;
use Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder\EventSourcingAggregateExample;
use Test\Ecotone\Modelling\Fixture\EventRevision\Person;
use Test\Ecotone\Modelling\Fixture\EventRevision\RegisterPerson;

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
final class EventSourcingAggregateTest extends TestCase
{
    public function test_registering_and_using_headers_in_apply_method(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [
                Person::class,
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );


        $ecotoneLite->sendCommand(new RegisterPerson('123', 'premium'));

        $person = $ecotoneLite->getAggregate(Person::class, '123');

        self::assertEquals('123', $person->getPersonId());
        self::assertEquals('premium', $person->getType());
        self::assertEquals(2, $person->getRegisteredWithRevision());
    }

    public function test_failing_on_using_apply_method_without_enterprise_mode(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [
                Person::class,
            ],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );


        $this->expectException(InvalidArgumentException::class);

        $ecotoneLite->sendCommand(new RegisterPerson('123', 'premium'));
    }

    public function test_calling_pure_event_sourced_aggregate_only_for_side_effects(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [
                EventSourcingAggregateExample::class,
            ],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );


        $stdClass = new stdClass();
        $ecotoneLite
            ->sendCommand(new CreateAggregate(123))
            ->sendCommandWithRouting('aggregate.onlySideEffects', $stdClass, metadata: ['aggregate.id' => 123]);

        $this->assertSame('test', $stdClass->name);
    }
}
