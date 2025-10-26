<?php

namespace Test\Ecotone\Modelling\Fixture\Blog;

use Ecotone\Modelling\Attribute\TargetAggregateIdentifier;

/**
 * Command where one identifier comes from command metadata and factory method generates the other
 * licence Apache-2.0
 */
class CreateArticleWithFactoryAssignedNullCommand
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

    public function getTitle(): ?string
    {
        return $this->title;
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
