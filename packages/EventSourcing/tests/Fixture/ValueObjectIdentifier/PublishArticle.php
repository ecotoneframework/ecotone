<?php

namespace Test\Ecotone\EventSourcing\Fixture\ValueObjectIdentifier;

use Ramsey\Uuid\UuidInterface;

class PublishArticle
{
    public function __construct(public UuidInterface $articleId, public string $content)
    {

    }
}