<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\DecisionModel;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\DecisionModel;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\EventSourcing\DynamicConsistencyBoundaryConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Test\ChannelTestSupport;
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
final class DecisionModelHandlerVariantsTest extends TestCase
{
    public function test_metadata_is_propagated_from_the_command_to_the_appended_event(): void
    {
        $counter = new InMemoryCounterServiceForVariantsTest();
        $handler = new HandlerWithReferenceForVariantsTest($counter);

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [HandlerWithReferenceForVariantsTest::class, CourseForVariantsTest::class, ItemAddedForVariantsTest::class],
            containerOrAvailableServices: [$handler, $counter],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->sendCommandWithRouting('addItem', new AddItemForVariantsTest('course-1'), metadata: ['correlationHeader' => 'abc']);

        $events = $ecotone->getEventStreamEvents('ecotone_event_stream');
        $this->assertCount(1, $events);
        $this->assertSame('abc', $events[0]->metadata['correlationHeader']);
    }

    public function test_reference_service_is_injected_alongside_a_decision_model(): void
    {
        $counter = new InMemoryCounterServiceForVariantsTest();
        $handler = new HandlerWithReferenceForVariantsTest($counter);

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [HandlerWithReferenceForVariantsTest::class, CourseForVariantsTest::class, ItemAddedForVariantsTest::class],
            containerOrAvailableServices: [$handler, $counter],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->sendCommandWithRouting('addItem', new AddItemForVariantsTest('course-1'));

        $this->assertSame(1, $counter->count);
    }

    public function test_asynchronous_handler_injecting_a_model_runs_via_run(): void
    {
        $handler = new AsyncHandlerForVariantsTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [AsyncHandlerForVariantsTest::class, CourseForVariantsTest::class, ItemAddedForVariantsTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->sendCommandWithRouting('addItemAsync', new AddItemForVariantsTest('course-1'));

        $this->assertCount(0, $ecotone->getEventStreamEvents('ecotone_event_stream'));

        $ecotone->run('async');

        $this->assertCount(1, $ecotone->getEventStreamEvents('ecotone_event_stream'));
    }

    public function test_event_handler_injecting_a_model_takes_the_tag_value_from_the_event_it_handles(): void
    {
        $handler = new EventHandlerWithModelForVariantsTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handler::class, CourseForVariantsTest::class, ItemAddedForVariantsTest::class, CourseOpenedForVariantsTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->publishEvent(new CourseOpenedForVariantsTest('course-1'));
        $ecotone->publishEvent(new CourseOpenedForVariantsTest('course-1'));
        $ecotone->publishEvent(new CourseOpenedForVariantsTest('course-2'));

        $this->assertCount(2, $ecotone->getEventStreamEvents('ecotone_event_stream'));
    }

    public function test_query_handler_with_a_model_replies_and_appends_nothing(): void
    {
        $handler = new QueryHandlerForVariantsTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [QueryHandlerForVariantsTest::class, CourseForVariantsTest::class, ItemAddedForVariantsTest::class, GetItemCountForVariantsTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [DynamicConsistencyBoundaryConfiguration::createWithDefaults()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->withEvents([new ItemAddedForVariantsTest('course-1'), new ItemAddedForVariantsTest('course-1')]);

        $itemCount = $ecotone->sendQueryWithRouting('getItemCount', new GetItemCountForVariantsTest('course-1'));

        $this->assertSame(2, $itemCount);
        $this->assertCount(2, $ecotone->getEventStreamEvents('ecotone_event_stream'));
    }

    public function test_output_channel_name_is_honoured_on_a_model_injecting_handler(): void
    {
        $handler = new OutputChannelHandlerForVariantsTest();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [OutputChannelHandlerForVariantsTest::class, CourseForVariantsTest::class, ItemAddedForVariantsTest::class],
            containerOrAvailableServices: [$handler],
            extensionObjects: [
                DynamicConsistencyBoundaryConfiguration::createWithDefaults(),
                \Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder::createQueueChannel('forwardedChannel'),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->sendCommandWithRouting('addItem', new AddItemForVariantsTest('course-1'));

        $this->assertCount(1, $ecotone->getEventStreamEvents('ecotone_event_stream'));

        $forwardedMessage = ChannelTestSupport::of($ecotone)->receiveMessageFrom('forwardedChannel');
        $this->assertNotNull($forwardedMessage);
    }
}

final readonly class AddItemForVariantsTest
{
    public function __construct(
        public string $courseId,
    ) {
    }
}

final readonly class ItemAddedForVariantsTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
    ) {
    }
}

#[DecisionModel]
final class CourseForVariantsTest
{
    private int $items = 0;

    #[Apply]
    public function itemAdded(ItemAddedForVariantsTest $event): void
    {
        $this->items++;
    }

    public function items(): int
    {
        return $this->items;
    }
}

final class InMemoryCounterServiceForVariantsTest
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }
}

final class HandlerWithReferenceForVariantsTest
{
    public function __construct(
        private readonly InMemoryCounterServiceForVariantsTest $counterService,
    ) {
    }

    #[CommandHandler('addItem')]
    public function addItem(
        AddItemForVariantsTest $command,
        CourseForVariantsTest $course,
        #[Reference] InMemoryCounterServiceForVariantsTest $counterService,
        #[Header('correlationHeader')] ?string $correlationHeader = null,
    ): array {
        $counterService->increment();

        return [new ItemAddedForVariantsTest($command->courseId)];
    }
}

final class AsyncHandlerForVariantsTest
{
    #[Asynchronous('async')]
    #[CommandHandler('addItemAsync', endpointId: 'addItemAsyncEndpoint')]
    public function addItem(AddItemForVariantsTest $command, CourseForVariantsTest $course): array
    {
        return [new ItemAddedForVariantsTest($command->courseId)];
    }
}

final readonly class GetItemCountForVariantsTest
{
    public function __construct(
        public string $courseId,
    ) {
    }
}

final class QueryHandlerForVariantsTest
{
    #[QueryHandler('getItemCount')]
    public function getItemCount(GetItemCountForVariantsTest $query, CourseForVariantsTest $course): int
    {
        return $course->items();
    }
}

final class OutputChannelHandlerForVariantsTest
{
    #[CommandHandler('addItem', outputChannelName: 'forwardedChannel')]
    public function addItem(AddItemForVariantsTest $command, CourseForVariantsTest $course): array
    {
        return [new ItemAddedForVariantsTest($command->courseId)];
    }
}

final readonly class CourseOpenedForVariantsTest
{
    public function __construct(
        #[EventTag('course')] public string $courseId,
    ) {
    }
}

final class EventHandlerWithModelForVariantsTest
{
    #[EventHandler]
    public function addFirstItem(CourseOpenedForVariantsTest $event, CourseForVariantsTest $course): array
    {
        return $course->items() === 0 ? [new ItemAddedForVariantsTest($event->courseId)] : [];
    }
}
