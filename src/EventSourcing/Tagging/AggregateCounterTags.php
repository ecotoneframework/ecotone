<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\Tagging;

use function array_flip;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\AggregateType;
use Ecotone\Api\Attribute\EventSourcingSaga;
use Ecotone\Api\Attribute\Saga;

use function in_array;
use function str_starts_with;
use function strlen;
use function substr;

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
final class AggregateCounterTags
{
    /**
     * @param array<string, class-string> $aggregateClassesByType
     * @param array<string, class-string> $aggregateClassesByTypeWithoutCounterTag
     */
    private function __construct(
        private readonly array $aggregateClassesByType,
        private readonly array $aggregateClassesByTypeWithoutCounterTag = [],
    ) {
    }

    /**
     * @param array<string, class-string> $aggregateClassesByType
     * @param array<string, class-string> $aggregateClassesByTypeWithoutCounterTag
     */
    public static function createWith(array $aggregateClassesByType, array $aggregateClassesByTypeWithoutCounterTag = []): self
    {
        return new self($aggregateClassesByType, $aggregateClassesByTypeWithoutCounterTag);
    }

    /**
     * @return array<class-string, ?string>
     */
    public static function declaredAggregateTypesOfCountedAggregatesIn(AnnotationFinder $annotationFinder): array
    {
        $sagaClasses = [
            ...$annotationFinder->findAnnotatedClasses(Saga::class),
            ...$annotationFinder->findAnnotatedClasses(EventSourcingSaga::class),
        ];

        $aggregateTypesByClass = [];
        foreach ($annotationFinder->findAnnotatedClasses(Aggregate::class) as $aggregateClass) {
            if (in_array($aggregateClass, $sagaClasses, true)) {
                continue;
            }

            $aggregateTypesByClass[$aggregateClass] = $annotationFinder->findAttributeForClass($aggregateClass, AggregateType::class)?->getName();
        }

        return $aggregateTypesByClass;
    }

    public function counts(string $aggregateType): bool
    {
        return isset($this->aggregateClassesByType[$aggregateType]);
    }

    /**
     * @return array{name: string, value: string}
     */
    public function counterTagOf(string $aggregateType, string $aggregateId): array
    {
        return ['name' => AggregateCounterTag::nameFor($aggregateType), 'value' => $aggregateId];
    }

    public function aggregateTypeCountedBy(string $tagName): ?string
    {
        if (! str_starts_with($tagName, AggregateCounterTag::NAME_PREFIX)) {
            return null;
        }

        $aggregateType = substr($tagName, strlen(AggregateCounterTag::NAME_PREFIX));

        return $this->counts($aggregateType) ? $aggregateType : null;
    }

    /**
     * @return ?class-string
     */
    public function aggregateClassWithoutCounterTagFor(string $counterTagName): ?string
    {
        if (! str_starts_with($counterTagName, AggregateCounterTag::NAME_PREFIX)) {
            return null;
        }

        return $this->aggregateClassesByTypeWithoutCounterTag[substr($counterTagName, strlen(AggregateCounterTag::NAME_PREFIX))] ?? null;
    }

    public function aggregateTypeOfClass(string $aggregateClass): ?string
    {
        $typesByClass = array_flip($this->aggregateClassesByType);

        return $typesByClass[$aggregateClass] ?? null;
    }
}
