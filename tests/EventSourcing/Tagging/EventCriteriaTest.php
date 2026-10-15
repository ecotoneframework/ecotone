<?php

declare(strict_types=1);

namespace Test\Ecotone\EventSourcing\Tagging;

use Ecotone\Api\EventSourcing\EventCriteria;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

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
 *
 * @internal
 */
final class EventCriteriaTest extends TestCase
{
    public function test_a_single_criterion_is_its_own_only_branch(): void
    {
        $criteria = EventCriteria::tag('course', 'course-1');

        self::assertSame([$criteria], $criteria->branches());
    }

    public function test_or_combines_two_criteria_into_two_branches(): void
    {
        $first = EventCriteria::tag('course', 'course-1');
        $second = EventCriteria::tag('course', 'course-2');

        $combined = $first->or($second);

        self::assertSame([$first, $second], $combined->branches());
    }

    public function test_or_chained_three_times_flattens_into_three_branches(): void
    {
        $first = EventCriteria::tag('course', 'course-1');
        $second = EventCriteria::tag('course', 'course-2');
        $third = EventCriteria::tag('course', 'course-3');

        $combined = $first->or($second)->or($third);

        self::assertSame([$first, $second, $third], $combined->branches());
    }

    public function test_and_tag_and_of_types_still_build_a_single_branch(): void
    {
        $criteria = EventCriteria::tag('course', 'course-1')
            ->andTag('student', 'student-1')
            ->ofTypes(self::class);

        self::assertSame([$criteria], $criteria->branches());
        self::assertSame(
            [['name' => 'course', 'value' => 'course-1'], ['name' => 'student', 'value' => 'student-1']],
            $criteria->tags,
        );
        self::assertSame([self::class], $criteria->eventTypes);
    }

    public function test_narrowing_an_or_combination_by_another_tag_is_rejected_instead_of_dropping_its_branches(): void
    {
        $combined = EventCriteria::tag('course', 'course-1')->or(EventCriteria::tag('course', 'course-2'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('andTag');

        $combined->andTag('student', 'student-1');
    }

    public function test_narrowing_an_or_combination_by_event_types_is_rejected_instead_of_dropping_its_branches(): void
    {
        $combined = EventCriteria::tag('course', 'course-1')->or(EventCriteria::tag('course', 'course-2'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ofTypes');

        $combined->ofTypes(self::class);
    }
}
