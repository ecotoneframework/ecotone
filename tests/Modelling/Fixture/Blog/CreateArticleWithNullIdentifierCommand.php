<?php

namespace Test\Ecotone\Modelling\Fixture\Blog;

use Ecotone\Modelling\Attribute\TargetAggregateIdentifier;

/**
 * Command that provides both identifiers but one is null for Article creation
 * licence Apache-2.0
 */
class CreateArticleWithNullIdentifierCommand
{
    #[TargetAggregateIdentifier]
    private string $author;
    #[TargetAggregateIdentifier]
    private ?string $title;
    private string $content;

    public function __construct(string $author, ?string $title, string $content)
    {
        $this->author = $author;
        $this->title = $title;
        $this->content = $content;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
