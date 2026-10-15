<?php

namespace Test\Ecotone\Modelling\Fixture\RepositoryShortcut;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class Twitter
{
    #[Identifier]
    private string $twitId;
    private string $content;

    public function __construct(string $twitId, string $content)
    {
        $this->twitId = $twitId;
        $this->content = $content;
    }

    #[QueryHandler('getContent')]
    public function getContent(): string
    {
        return $this->content;
    }

    #[CommandHandler('changeContent')]
    public function changeContent(string $content): void
    {
        $this->content = $content;
    }
}
