<?php

namespace Test\Ecotone\Modelling\Fixture\Blog;

/**
 * Command that provides no identifiers for Article creation
 * licence Apache-2.0
 */
class CreateArticleWithoutIdentifiersCommand
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
