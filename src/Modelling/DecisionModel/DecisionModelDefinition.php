<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel;

use function array_diff;
use function array_keys;
use function array_values;

use Ecotone\Api\EventSourcing\EventCriteria;

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
final class DecisionModelDefinition
{
    /**
     * @param string[] $tagNames
     * @param class-string[] $handledEventClasses
     * @param array<string, string> $literalTagValues the scope tags every handled event fixes with a class-level #[EventTag] literal
     */
    public function __construct(
        private readonly string $className,
        private readonly array $tagNames,
        private readonly array $handledEventClasses,
        private readonly array $literalTagValues = [],
    ) {
    }

    public function className(): string
    {
        return $this->className;
    }

    /**
     * @return string[]
     */
    public function tagNames(): array
    {
        return $this->tagNames;
    }

    /**
     * @return class-string[]
     */
    public function handledEventClasses(): array
    {
        return $this->handledEventClasses;
    }

    /**
     * @return array<string, string>
     */
    public function literalTagValues(): array
    {
        return $this->literalTagValues;
    }

    /**
     * @return string[]
     */
    public function tagNamesResolvedFromTheMessage(): array
    {
        return array_values(array_diff($this->tagNames, array_keys($this->literalTagValues)));
    }

    /**
     * @param array<string, string> $tagValuesByName
     */
    public function toCriteria(array $tagValuesByName): EventCriteria
    {
        $criterion = EventCriteria::any();
        foreach ($this->tagNames as $tagName) {
            $criterion = $criterion->tags === []
                ? EventCriteria::tag($tagName, $tagValuesByName[$tagName])
                : $criterion->andTag($tagName, $tagValuesByName[$tagName]);
        }

        return $criterion->ofTypes(...$this->handledEventClasses);
    }
}
