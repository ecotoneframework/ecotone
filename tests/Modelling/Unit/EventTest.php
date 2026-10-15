<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Lite\Test\Configuration\InMemoryRepositoryBuilder;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class EventTest extends TestCase
{
    public function test_event_created_with_explicit_metadata_preserves_it_through_the_event_store(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [],
            [],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([InMemoryRepositoryBuilder::createDefaultEventSourcedRepository()])
                ->withEnabledModulePackageNames([])
        );

        $metadata = [
            MessageHeaders::MESSAGE_ID => 'uuid',
            MessageHeaders::TIMESTAMP => 123,
        ];

        $ecotone->withEventStream('explicitMetadataStream', [Event::create(new stdClass(), $metadata)]);

        $recordedEvent = $ecotone->getGateway(EventStore::class)->load('explicitMetadataStream')[0];

        $this->assertSame('uuid', $recordedEvent->metadata[MessageHeaders::MESSAGE_ID]);
        $this->assertSame(123, $recordedEvent->metadata[MessageHeaders::TIMESTAMP]);
    }
}
