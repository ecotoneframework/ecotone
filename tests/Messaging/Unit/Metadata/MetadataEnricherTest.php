<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Metadata;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\Revision;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Modelling\WithEvents;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class MetadataEnricherTest extends TestCase
{
    public function test_revision_header_is_resolved_from_the_recorded_events_class_attribute(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting([RevisionAggregate::class], enabledModules: []);

        $ecotone->sendCommandWithRouting('revisionAggregate.createWithRevisionedEvent', 'agg-1');

        $this->assertSame(2, $ecotone->popRecordedEventHeaders()[0]->get(MessageHeaders::REVISION));
    }

    public function test_revision_header_defaults_to_1_when_the_recorded_events_class_has_no_revision_attribute(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting([RevisionAggregate::class], enabledModules: []);

        $ecotone->sendCommandWithRouting('revisionAggregate.createWithUnrevisionedEvent', 'agg-2');

        $this->assertSame(1, $ecotone->popRecordedEventHeaders()[0]->get(MessageHeaders::REVISION));
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
#[Revision(2)]
final class RevisionedEvent
{
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class UnrevisionedEvent
{
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
#[Aggregate]
final class RevisionAggregate
{
    use WithEvents;

    #[Identifier]
    private string $id;

    #[CommandHandler('revisionAggregate.createWithRevisionedEvent')]
    public static function createWithRevisionedEvent(string $id): self
    {
        $self = new self();
        $self->id = $id;
        $self->recordThat(new RevisionedEvent());

        return $self;
    }

    #[CommandHandler('revisionAggregate.createWithUnrevisionedEvent')]
    public static function createWithUnrevisionedEvent(string $id): self
    {
        $self = new self();
        $self->id = $id;
        $self->recordThat(new UnrevisionedEvent());

        return $self;
    }
}
