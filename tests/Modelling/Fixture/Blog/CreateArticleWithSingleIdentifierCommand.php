<?php

namespace Test\Ecotone\Modelling\Fixture\Blog;

use Ecotone\Api\Attribute\TargetIdentifier;

/**
 * Command that provides only one identifier for Article creation
 * licence Apache-2.0
 */
class CreateArticleWithSingleIdentifierCommand
{
    #[TargetIdentifier]
    private string $author;
    private string $content;

    public function __construct(string $author, string $content)
    {
        $this->author = $author;
        $this->content = $content;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
