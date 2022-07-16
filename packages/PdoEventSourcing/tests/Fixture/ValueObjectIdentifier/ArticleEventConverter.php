<?php

namespace Test\Ecotone\EventSourcing\Fixture\ValueObjectIdentifier;

use Ecotone\Messaging\Attribute\Converter;
use Ramsey\Uuid\Uuid;

class ArticleEventConverter
{
    #[Converter]
    public function from(ArticleWasPublished $articleWasPublished): array
    {
        return ['articleId' => $articleWasPublished->articleId, 'content' => $articleWasPublished->content];
    }

    #[Converter]
    public function to(array $articleWasPublished): ArticleWasPublished
    {
        return new ArticleWasPublished(Uuid::fromString($articleWasPublished['articleId']), $articleWasPublished['content']);
    }
}
