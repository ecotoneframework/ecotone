<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Metadata;

use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\Attribute\Revision;
use ReflectionAttribute;
use ReflectionObject;

/**
 * licence Apache-2.0
 */
final class RevisionMetadataEnricher
{
    public static function enrich(array $metadata, object $message): array
    {
        $metadata[MessageHeaders::REVISION] = 1;

        $reflection = new ReflectionObject($message);
        $revisionAttributes = $reflection->getAttributes(Revision::class, ReflectionAttribute::IS_INSTANCEOF);

        if (! empty($revisionAttributes)) {
            /** @var Revision $revision */
            $revision = $revisionAttributes[0]->newInstance();
            $metadata[MessageHeaders::REVISION] = $revision->getRevision();
        }


        return $metadata;
    }
}
