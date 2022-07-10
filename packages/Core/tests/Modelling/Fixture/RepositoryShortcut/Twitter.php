<?php

namespace Test\Ecotone\Modelling\Fixture\RepositoryShortcut;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
class Twitter
{
    #[AggregateIdentifier]
    private string $twitId;
    private string $content;

    public function __construct(string $twitId, string $content)
    {
        $this->twitId = $twitId;
        $this->content = $content;
    }

    #[QueryHandler("getContent")]
    public function getContent(): string
    {
        return $this->content;
    }

    #[CommandHandler("changeContent")]
    public function changeContent(string $content): void
    {
        $this->content = $content;
    }
}