<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
/**
 * licence Apache-2.0
 */
class Twitter
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $twitId;
    private string $content;

    #[QueryHandler('getContent')]
    public function getContent(): string
    {
        return $this->content;
    }

    #[CommandHandler('changeContent')]
    public function changeContent(string $content): array
    {
        return [new TwitContentWasChanged($this->twitId, $content)];
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
