<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\EventSourcing\Stream;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Test\LicenceTesting;
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
final class DecisionModelStreamTest extends TestCase
{
    public function test_stream_on_the_handler_method_wins_over_stream_on_the_class(): void
    {
        if (! class_exists(Stream::class)) {
            $this->markTestSkipped('Ecotone\Api\EventSourcing\Stream is not autoloadable outside a monorepo context that includes ecotone/pdo-event-sourcing.');
        }

        $handler = new StreamHandlerForStreamTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, CourseForStreamTest::class, CourseDefinedForStreamTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->sendCommand(new DefineCourseForStreamTest('course-1', 10));

        $this->assertCount(0, $ecotone->getEventStreamEvents('ecotone_event_stream'));
        $this->assertCount(1, $ecotone->getEventStreamEvents('method_level_stream'));
    }
}

final readonly class DefineCourseForStreamTest
{
    public function __construct(
        public string $courseId,
        public int $capacity,
    ) {
    }
}

final readonly class CourseDefinedForStreamTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
        public int $capacity,
    ) {
    }
}

#[DecisionModel]
final class CourseForStreamTest
{
    private int $capacity = 0;

    #[Apply]
    public function defined(CourseDefinedForStreamTest $event): void
    {
        $this->capacity = $event->capacity;
    }

    public function capacity(): int
    {
        return $this->capacity;
    }
}

#[Stream('class_level_stream')]
final class StreamHandlerForStreamTest
{
    #[Stream('method_level_stream')]
    #[CommandHandler]
    public function define(DefineCourseForStreamTest $command, CourseForStreamTest $course): array
    {
        return [new CourseDefinedForStreamTest($command->courseId, $command->capacity)];
    }
}
