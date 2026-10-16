<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite\Test;

use DateTimeImmutable;
use Ecotone\Api\Attribute\AggregateType;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\ExtensionObject\TestConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class SeededEventsAtTimeTest extends TestCase
{
    public function test_a_stream_seeded_at_a_time_loads_back_with_that_timestamp(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            testConfiguration: TestConfiguration::createWithDefaults()->withTimeAt(new DateTimeImmutable('2024-03-01 10:00:00')),
        );

        $ecotone
            ->withEventStream('tickets', [new TicketOpenedForSeeding('t-1')], at: new DateTimeImmutable('2023-01-01 08:00:00'))
            ->withEventStream('tickets', [new TicketClosedForSeeding('t-1')]);

        self::assertSame(
            [(new DateTimeImmutable('2023-01-01 08:00:00'))->getTimestamp(), (new DateTimeImmutable('2024-03-01 10:00:00'))->getTimestamp()],
            array_map(fn (Event $event): int => $event->metadata[MessageHeaders::TIMESTAMP], $ecotone->getEventStreamEvents('tickets')),
        );
    }

    public function test_a_seeded_event_keeps_the_timestamp_it_already_carries(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting();

        $ecotone->withEventStream('tickets', [Event::create(new TicketOpenedForSeeding('t-1'), [MessageHeaders::TIMESTAMP => 1_600_000_000])], at: new DateTimeImmutable('2023-01-01 08:00:00'));

        self::assertSame(1_600_000_000, $ecotone->getEventStreamEvents('tickets')[0]->metadata[MessageHeaders::TIMESTAMP]);
    }

    public function test_an_aggregate_seeded_at_a_time_records_its_history_at_that_time(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [TicketForSeeding::class],
            testConfiguration: TestConfiguration::createWithDefaults()->withTimeAt(new DateTimeImmutable('2024-03-01 10:00:00')),
        );

        $ecotone
            ->withEventsFor('t-1', TicketForSeeding::class, [new TicketOpenedForSeeding('t-1')], at: new DateTimeImmutable('2023-01-01 08:00:00'))
            ->withEventsFor('t-1', TicketForSeeding::class, [new TicketClosedForSeeding('t-1')], aggregateVersion: 1);

        self::assertSame(
            [(new DateTimeImmutable('2023-01-01 08:00:00'))->getTimestamp(), (new DateTimeImmutable('2024-03-01 10:00:00'))->getTimestamp()],
            array_map(fn (Event $event): int => $event->metadata[MessageHeaders::TIMESTAMP], $ecotone->getEventStreamEvents('ecotone_event_stream')),
        );
    }
}

final class TicketOpenedForSeeding
{
    public function __construct(public string $ticketId)
    {
    }
}

final class TicketClosedForSeeding
{
    public function __construct(public string $ticketId)
    {
    }
}

#[EventSourcingAggregate]
#[AggregateType('TicketForSeeding')]
final class TicketForSeeding
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $ticketId;

    #[Apply]
    public function opened(TicketOpenedForSeeding $event): void
    {
        $this->ticketId = $event->ticketId;
    }

    #[Apply]
    public function closed(TicketClosedForSeeding $event): void
    {
    }
}
