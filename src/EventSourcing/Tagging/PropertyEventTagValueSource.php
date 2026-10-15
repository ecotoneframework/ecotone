<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\Tagging;

use ReflectionProperty;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
final class PropertyEventTagValueSource implements EventTagValueSource
{
    private readonly ReflectionProperty $reflectionProperty;

    public function __construct(
        private readonly string $tagName,
        string $className,
        string $propertyName,
    ) {
        $this->reflectionProperty = new ReflectionProperty($className, $propertyName);
    }

    public function tagName(): string
    {
        return $this->tagName;
    }

    public function resolveValues(object $event): array
    {
        if (! $this->reflectionProperty->isInitialized($event)) {
            return [];
        }

        return EventTagValueNormalizer::normalize($this->tagName, $this->reflectionProperty->getValue($event));
    }
}
