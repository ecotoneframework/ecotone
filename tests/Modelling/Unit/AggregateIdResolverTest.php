<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Modelling\NoCorrectIdentifierDefinedException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use stdClass;

/**
 * licence Apache-2.0
 *
 * @internal
 */
class AggregateIdResolverTest extends TestCase
{
    public function test_resolving_a_scalar_aggregate_id(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting([ScalarIdAggregate::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone->sendCommandWithRouting('scalarIdAggregate.create', 123);

        $this->assertSame(123, $ecotone->getAggregate(ScalarIdAggregate::class, 123)->getId());
    }

    public function test_resolving_an_aggregate_id_that_implements_stringable(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting([StringableIdAggregate::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $id = Uuid::fromString('5495ec27-c286-48fe-aed4-2548c1113c37');
        $ecotone->sendCommandWithRouting('stringableIdAggregate.create', $id);

        $this->assertNotNull($ecotone->getAggregate(StringableIdAggregate::class, $id->toString()));
    }

    public function test_throwing_exception_if_aggregate_id_is_an_object_without_a_to_string_method(): void
    {
        $this->expectException(NoCorrectIdentifierDefinedException::class);

        $ecotone = EcotoneLite::bootstrapFlowTesting([ScalarIdAggregate::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
        $ecotone->sendCommandWithRouting('scalarIdAggregate.create', new stdClass());
    }

    public function test_throwing_exception_if_aggregate_id_is_an_array(): void
    {
        $this->expectException(NoCorrectIdentifierDefinedException::class);

        $ecotone = EcotoneLite::bootstrapFlowTesting([ScalarIdAggregate::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
        $ecotone->sendCommandWithRouting('scalarIdAggregate.create', ['johny']);
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
#[Aggregate]
final class ScalarIdAggregate
{
    #[Identifier]
    private mixed $id;

    #[CommandHandler('scalarIdAggregate.create')]
    public static function create(mixed $id): self
    {
        $self = new self();
        $self->id = $id;

        return $self;
    }

    public function getId(): mixed
    {
        return $this->id;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
#[Aggregate]
final class StringableIdAggregate
{
    #[Identifier]
    private UuidInterface $id;

    #[CommandHandler('stringableIdAggregate.create')]
    public static function create(UuidInterface $id): self
    {
        $self = new self();
        $self->id = $id;

        return $self;
    }
}
