<?php

declare(strict_types=1);

namespace Test\Ecotone\Projecting;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Projecting\Attribute\Partitioned;
use Ecotone\Projecting\Attribute\Polling;
use Ecotone\Projecting\Attribute\ProjectionV2;
use Ecotone\Projecting\Attribute\Streaming;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Enterprise
 * @internal
 */
final class ProjectionApiTest extends TestCase
{
    public function test_it_throws_exception_when_polling_and_streaming_are_used_together(): void
    {
        $projection = new #[ProjectionV2('test_projection'), Polling('test_endpoint'), Streaming('test_channel')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'test_projection' cannot use both #[Polling] and #[Streaming] attributes.");

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );
    }

    public function test_it_throws_exception_when_polling_and_partitioned_are_used_together(): void
    {
        $projection = new #[ProjectionV2('test_projection'), Polling('test_endpoint'), Partitioned] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'test_projection' cannot use both #[Polling] and #[Partitioned] attributes.");

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );
    }

    public function test_it_throws_exception_when_partitioned_and_streaming_are_used_together(): void
    {
        $projection = new #[ProjectionV2('test_projection'), Partitioned, Streaming('test_channel')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'test_projection' cannot use both #[Partitioned] and #[Streaming] attributes.");

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );
    }
}
