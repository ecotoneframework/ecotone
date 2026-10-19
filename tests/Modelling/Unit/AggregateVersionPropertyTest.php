<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\Version;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Messaging\Config\ConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class AggregateVersionPropertyTest extends TestCase
{
    public function test_an_aggregate_declaring_version_on_two_properties_is_refused_at_bootstrap(): void
    {
        $aggregate = new #[Aggregate] class () {
            #[Identifier]
            private string $orderId = '';
            #[Version]
            private int $version = 0;
            #[Version]
            private int $revision = 0;

            #[CommandHandler('order.place')]
            public static function place(string $orderId): self
            {
                $self = new self();
                $self->orderId = $orderId;

                return $self;
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            '%s declares #[Version] on two properties, `version` and `revision`, yet an aggregate has one version: Ecotone compares it on save and increases it on every change. Keep #[Version] on one property and remove it from the other, or use the %s trait, which declares the single version property.',
            $aggregate::class,
            WithAggregateVersioning::class,
        ));

        EcotoneLite::bootstrapFlowTesting([$aggregate::class], enabledModules: []);
    }

    public function test_an_aggregate_mixing_the_versioning_trait_with_its_own_version_property_is_refused_at_bootstrap(): void
    {
        $aggregate = new #[Aggregate] class () {
            use WithAggregateVersioning;

            #[Identifier]
            private string $orderId = '';
            #[Version]
            private int $revision = 0;

            #[CommandHandler('order.place')]
            public static function place(string $orderId): self
            {
                $self = new self();
                $self->orderId = $orderId;

                return $self;
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('declares #[Version] on two properties, `revision` and `version`');

        EcotoneLite::bootstrapFlowTesting([$aggregate::class], enabledModules: []);
    }
}
