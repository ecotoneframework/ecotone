<?php

namespace Test\Ecotone\EventSourcing\Fixture\ValueObjectIdentifier;

use Ramsey\Uuid\UuidInterface;

class ArticleWasPublished
{
    public function __construct(public UuidInterface $articleId, public string $content)
    {
    }
}
