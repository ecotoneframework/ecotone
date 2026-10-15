<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\Tagging;

use function array_key_exists;
use function class_exists;

use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Messaging\Config\ConfigurationException;

use function preg_match_all;

use ReflectionAttribute;
use ReflectionClass;

use function sprintf;

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
final class AggregateCounterTagGuard
{
    private const TAG_NAME_COLUMN_LENGTH = 100;

    /**
     * @param class-string[] $classesWithoutCounterTag
     * @param array<class-string, ?string> $aggregateTypesByClass
     */
    public static function assertEveryClassWithoutCounterTagIsAStateStoredAggregate(array $classesWithoutCounterTag, array $aggregateTypesByClass): void
    {
        foreach ($classesWithoutCounterTag as $className) {
            if (! class_exists($className) || ! array_key_exists($className, $aggregateTypesByClass)) {
                throw ConfigurationException::create(sprintf(
                    'DynamicConsistencyBoundaryConfiguration::withoutCounterTagFor() names %s, which is not a state-stored #[Aggregate] -- only a state-stored aggregate has a counter tag to opt out of. Sagas carry no counter, and an event-sourced aggregate is guarded by its own stream.',
                    $className,
                ));
            }

            if ((new ReflectionClass($className))->getAttributes(EventSourcingAggregate::class, ReflectionAttribute::IS_INSTANCEOF) !== []) {
                throw ConfigurationException::create(sprintf(
                    "DynamicConsistencyBoundaryConfiguration::withoutCounterTagFor() names %s, which is an #[EventSourcingAggregate] -- it has no counter tag to drop: its stream's own version check guards it, and that cannot be switched off. Drop it from withoutCounterTagFor().",
                    $className,
                ));
            }
        }
    }

    /**
     * @param array<class-string, ?string> $aggregateTypesByClass
     * @param array<class-string, array<array{kind: string, name: string, member: ?string, value: ?string}>> $rawTagDefinitions
     */
    public static function assertEveryAggregateHasACountableType(array $aggregateTypesByClass, array $rawTagDefinitions): void
    {
        $aggregateClassesByCounterTagName = [];
        foreach ($aggregateTypesByClass as $aggregateClass => $aggregateType) {
            if ($aggregateType === null) {
                throw ConfigurationException::create(sprintf(
                    "Aggregate %s must declare #[AggregateType('...')] when Dynamic Consistency Boundary is enabled -- every save of an aggregate bumps its counter tag '%s<AggregateType>', so the aggregate needs a stable type name rather than its class name. For an event-sourced aggregate with existing history, use the aggregate type its stream already stores.",
                    $aggregateClass,
                    AggregateCounterTag::NAME_PREFIX,
                ));
            }

            $counterTagName = AggregateCounterTag::nameFor($aggregateType);
            $characterCount = preg_match_all('/./su', $counterTagName);
            if ($characterCount > self::TAG_NAME_COLUMN_LENGTH) {
                throw ConfigurationException::create(sprintf(
                    "Aggregate %s has #[AggregateType('%s')], so its counter tag '%s' is %d characters, but the tag tables' tag_name column holds at most %d characters. Declare a shorter #[AggregateType] on %s.",
                    $aggregateClass,
                    $aggregateType,
                    $counterTagName,
                    $characterCount,
                    self::TAG_NAME_COLUMN_LENGTH,
                    $aggregateClass,
                ));
            }

            $aggregateClassesByCounterTagName[$counterTagName] = $aggregateClass;
        }

        foreach ($rawTagDefinitions as $eventClass => $entries) {
            foreach ($entries as $entry) {
                if (isset($aggregateClassesByCounterTagName[$entry['name']])) {
                    throw ConfigurationException::create(sprintf(
                        "#[EventTag] name '%s' on %s is the counter tag of aggregate %s -- a user tag cannot share it, or appending that event would move the aggregate's counter. Rename the event tag, or change the aggregate's #[AggregateType].",
                        $entry['name'],
                        $eventClass,
                        $aggregateClassesByCounterTagName[$entry['name']],
                    ));
                }
            }
        }
    }
}
