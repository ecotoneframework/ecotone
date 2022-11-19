<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Metadata;

use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Metadata\RevisionMetadataEnricher;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\Messaging\Unit\MessageWithRevision;

/**
 * @internal
 */
final class MetadataEnricherTest extends TestCase
{
    public function test_revision_will_resolved_from_object_attribute(): void
    {
        $metadata = [MessageHeaders::REVISION => 1];
        $metadata = RevisionMetadataEnricher::enrich($metadata, new MessageWithRevision());

        self::assertArrayHasKey(MessageHeaders::REVISION, $metadata);
        self::assertEquals(2, $metadata[MessageHeaders::REVISION]);
    }

    public function test_without_attribute_revision_will_have_value_1(): void
    {
        $metadata = [];
        $metadata = RevisionMetadataEnricher::enrich($metadata, new stdClass());

        self::assertArrayHasKey(MessageHeaders::REVISION, $metadata);
        self::assertEquals(1, $metadata[MessageHeaders::REVISION]);
    }
}
