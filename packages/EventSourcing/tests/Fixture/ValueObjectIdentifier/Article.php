<?php

namespace Test\Ecotone\EventSourcing\Fixture\ValueObjectIdentifier;

use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\WithAggregateVersioning;
use Ramsey\Uuid\UuidInterface;

#[EventSourcingAggregate]
class Article
{
    use WithAggregateVersioning;

    #[AggregateIdentifier]
    private UuidInterface $articleId;
    private string $content;

    #[CommandHandler]
    public static function publish(PublishArticle $command): array
    {
        return [new ArticleWasPublished($command->articleId, $command->content)];
    }

    #[EventSourcingHandler]
    public function apply(ArticleWasPublished $event): void
    {
        $this->articleId = $event->articleId;
        $this->content = $event->content;
    }

    #[QueryHandler("article.getContent")]
    public function getContent(): string
    {
        return $this->content;
    }
}