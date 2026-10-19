<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\Repository;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class RepositoryAttributeOnInterfaceTest extends TestCase
{
    public function test_class_level_repository_attribute_on_an_interface_is_refused_with_the_method_level_alternative(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            '#[Repository] is placed on the interface Test\Ecotone\Modelling\Unit\BasketRepositoryWithClassLevelAttribute. '
            . 'A class-level #[Repository] marks a custom repository implementation, a class implementing Ecotone\Api\Modelling\StateStoredRepository or Ecotone\Api\Modelling\EventSourcedRepository, and an interface cannot be one. '
            . 'To have Ecotone generate the repository for Test\Ecotone\Modelling\Unit\BasketRepositoryWithClassLevelAttribute, remove the class-level attribute and put #[Repository] on each of its methods, for example `#[Repository] public function save(Basket $basket): void;` and `#[Repository] public function get(string $basketId): ?Basket;`. '
            . 'To provide your own storage, put the class-level #[Repository] on a class implementing one of those two interfaces instead.'
        );

        EcotoneLite::bootstrapFlowTesting(
            [BasketWithClassLevelRepositoryAttribute::class, BasketRepositoryWithClassLevelAttribute::class],
            [],
            enabledModules: [],
        );
    }
}

#[Aggregate]
final class BasketWithClassLevelRepositoryAttribute
{
    public function __construct(#[Identifier] public string $basketId)
    {
    }
}

#[Repository]
interface BasketRepositoryWithClassLevelAttribute
{
    public function save(BasketWithClassLevelRepositoryAttribute $basket): void;

    public function get(string $basketId): ?BasketWithClassLevelRepositoryAttribute;
}
