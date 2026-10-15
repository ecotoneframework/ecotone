<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Api\Modelling\WithEvents;

#[EventSourcingAggregate(true)]
/**
 * licence Apache-2.0
 */
class TwitterWithRecorder
{
    use WithAggregateVersioning;
    use WithEvents;

    #[Identifier]
    private string $twitId;
    private string $content;

    public function getContent(): string
    {
        return $this->content;
    }

    public static function create(string $twitId, string $content): self
    {
        $self = new self();
        $self->recordThat(new TwitWasCreated($twitId, $content));

        return $self;
    }

    public function changeContent(string $content): void
    {
        $this->recordThat(new TwitContentWasChanged($this->twitId, $content));
    }

    #[Apply]
    public function whenTwitWasCreated(TwitWasCreated $event): void
    {
        $this->twitId = $event->twitId;
        $this->content = $event->content;
    }

    #[Apply]
    public function whenTwitContentWasChanged(TwitContentWasChanged $event): void
    {
        $this->content = $event->content;
    }
}
