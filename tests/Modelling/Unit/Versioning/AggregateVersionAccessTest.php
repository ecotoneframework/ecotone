<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit\Versioning;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class AggregateVersionAccessTest extends TestCase
{
    public function test_aggregate_using_versioning_trait_exposes_its_version(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting([VersionedBasket::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotone
            ->sendCommandWithRouting('versionedBasket.create', 'basket-1')
            ->sendCommandWithRouting('versionedBasket.addProduct', 'product-1', metadata: ['aggregate.id' => 'basket-1']);

        $this->assertSame(2, $ecotone->getAggregate(VersionedBasket::class, 'basket-1')->getVersion());
    }
}

/**
 * licence Apache-2.0
 * @internal
 */
#[EventSourcingAggregate]
final class VersionedBasket
{
    use WithAggregateVersioning;

    #[Identifier]
    public string $basketId;

    #[CommandHandler('versionedBasket.create')]
    public static function create(string $basketId): array
    {
        return [new VersionedBasketCreated($basketId)];
    }

    #[CommandHandler('versionedBasket.addProduct')]
    public function addProduct(string $productId): array
    {
        return [new VersionedBasketCreated($this->basketId)];
    }

    #[Apply]
    public function applyCreated(VersionedBasketCreated $event): void
    {
        $this->basketId = $event->basketId;
    }
}

/**
 * licence Apache-2.0
 * @internal
 */
final class VersionedBasketCreated
{
    public function __construct(public string $basketId)
    {
    }
}
