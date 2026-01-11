<?php

declare(strict_types=1);

namespace Test\Ecotone\Projecting;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Consumer\ConsumerPositionTracker;
use Ecotone\Messaging\Consumer\InMemory\InMemoryConsumerPositionTracker;
use Ecotone\Messaging\Conversion\MediaType;
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

    public function test_streaming_projection_does_not_require_from_stream_attribute(): void
    {
        $positionTracker = new InMemoryConsumerPositionTracker();

        $projection = new #[ProjectionV2('streaming_projection'), Streaming('streaming_channel')] class {
            public array $events = [];

            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->events[] = $event;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection, ConsumerPositionTracker::class => $positionTracker],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createStreamingChannel('streaming_channel', conversionMediaType: MediaType::createApplicationXPHP()),
                ])
        );

        $this->assertNotNull($ecotone);
    }

    public function test_non_streaming_projection_requires_from_stream_attribute(): void
    {
        $projection = new #[ProjectionV2('non_streaming_projection')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'non_streaming_projection' must have at least one #[FromStream] or #[FromAggregateStream] attribute");

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );
    }
}
