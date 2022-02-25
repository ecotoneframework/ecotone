<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
class Twitter
{
    use WithAggregateVersioning;

    #[AggregateIdentifier]
    private string $twitId;
    private string $content;

    #[QueryHandler("getContent")]
    public function getContent(): string
    {
        return $this->content;
    }

    #[CommandHandler("changeContent")]
    public function changeContent(string $content): array
    {
        return [new TwitContentWasChanged($this->twitId, $content)];
    }

    #[EventSourcingHandler]
    public function whenTwitWasCreated(TwitWasCreated $event): void
    {
        $this->twitId = $event->twitId;
        $this->content = $event->content;
    }

    #[EventSourcingHandler]
    public function whenTwitContentWasChanged(TwitContentWasChanged $event): void
    {
        $this->content = $event->content;
    }
}