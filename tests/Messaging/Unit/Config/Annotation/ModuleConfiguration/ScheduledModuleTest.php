<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Attribute;
use Ecotone\Api\Attribute\Before;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Scheduled;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\NullableMessageChannel;
use PHPUnit\Framework\TestCase;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
final class ScheduledModuleTestMarker
{
}

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class ScheduledModuleTest extends TestCase
{
    public function test_attributes_on_scheduled_method_trigger_pointcut_interceptors(): void
    {
        $service = new class () {
            #[Scheduled(requestChannelName: NullableMessageChannel::CHANNEL_NAME, endpointId: 'scheduledWithMarker')]
            #[ScheduledModuleTestMarker]
            public function poll(): ?string
            {
                return 'payload';
            }
        };

        $counter = new class () {
            private int $count = 0;

            #[Before(pointcut: ScheduledModuleTestMarker::class)]
            public function increment(): void
            {
                $this->count++;
            }

            #[QueryHandler('scheduledMarker.count')]
            public function count(): int
            {
                return $this->count;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$service::class, $counter::class],
            [$service, $counter],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([]),
        );

        $ecotone->run('scheduledWithMarker', ExecutionPollingMetadata::createWithTestingSetup(1, 1));

        $this->assertSame(
            1,
            $ecotone->sendQueryWithRouting('scheduledMarker.count'),
            'Method-level attributes on a #[Scheduled] method must reach the channel adapter so attribute-pointcut interceptors fire.',
        );
    }
}
